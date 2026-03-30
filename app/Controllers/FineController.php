<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Client;
use App\Models\FinancialEntry;
use App\Models\Rental;
use App\Models\TrafficFine;
use App\Models\Vehicle;

class FineController extends Controller
{
    public function summary(): void
    {
        $rentalId = (int)($_GET['rental_id'] ?? 0);
        if ($rentalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['qtd' => 0, 'valor_total' => 0]);
            return;
        }

        $summary = (new TrafficFine())->summaryByRentalId($rentalId);
        header('Content-Type: application/json');
        echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function index(): void
    {
        $model = new TrafficFine();

        $filters = [
            'rental_id' => $_GET['rental_id'] ?? null,
            'client_id' => $_GET['client_id'] ?? null,
            'placa' => $_GET['placa'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];

        $rentals = (new Rental())->all([]);
        $clients = (new Client())->all();
        $vehicles = (new Vehicle())->all();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $pagination = $model->paginate($filters, $page, $perPage);
        $totalPages = max(1, (int)ceil(($pagination['total'] ?? 0) / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $pagination = $model->paginate($filters, $page, $perPage);
        }
        $fines = $pagination['data'];
        $allFilteredFines = $model->all($filters);

        $totals = [
            'qtd' => count($allFilteredFines),
            'valor' => array_reduce($allFilteredFines, static fn (float $sum, array $fine): float => $sum + (float)$fine['valor'], 0.0),
        ];

        $statusOptions = $this->statusOptions();
        $currentPage = $page;
        $queryParams = $filters;
        $this->view('fines/index', compact('filters', 'rentals', 'clients', 'vehicles', 'fines', 'totals', 'statusOptions', 'currentPage', 'totalPages', 'queryParams'));
    }

    public function store(): void
    {
        validateCsrf();

        $payload = $this->finePayload(false);
        $fineId = (new TrafficFine())->create($payload);
        $this->syncFinancial((new TrafficFine())->find($fineId));

        flash('success', 'Multa cadastrada com sucesso.');
        $this->redirect('/fines');
    }

    public function update(): void
    {
        validateCsrf();

        $id = (int)($_POST['id'] ?? $_POST['fine_id'] ?? 0);
        $model = new TrafficFine();
        $current = $model->find($id);
        if (!$current) {
            flash('error', 'Multa não encontrada.');
            $this->redirect('/fines');
        }

        $payload = $this->finePayload(true);
        $payload['id'] = $id;
        $model->update($payload);
        $this->syncFinancial($model->find($id));

        flash('success', 'Multa atualizada com sucesso.');
        $this->redirect('/fines');
    }

    public function delete(): void
    {
        validateCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $fine = (new TrafficFine())->find($id);
        if (!$fine) {
            flash('error', 'Multa não encontrada.');
            $this->redirect('/fines');
        }

        if (!empty($fine['comprovante_path'])) {
            $fullPath = rtrim(UPLOADS_PATH, '/') . '/' . ltrim((string)$fine['comprovante_path'], '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $financialModel = new FinancialEntry();
        $financialModel->deleteByFineId($id);
        (new TrafficFine())->delete($id);

        flash('success', 'Multa excluída com sucesso.');
        $this->redirect('/fines');
    }

    public function uploadAttachment(): void
    {
        validateCsrf();

        $id = (int)($_POST['id'] ?? $_POST['fine_id'] ?? 0);
        $fineModel = new TrafficFine();
        $fine = $fineModel->find($id);
        if (!$fine) {
            flash('error', 'Multa não encontrada.');
            $this->redirect('/fines');
        }

        $uploadError = (int)($_FILES['comprovante']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK || empty($_FILES['comprovante']['tmp_name']) || !is_uploaded_file($_FILES['comprovante']['tmp_name'])) {
            flash('error', 'Selecione um comprovante válido.');
            $this->redirect('/fines');
        }

        $tmpName = $_FILES['comprovante']['tmp_name'];
        $original = (string)($_FILES['comprovante']['name'] ?? 'comprovante');
        $mime = mime_content_type($tmpName) ?: 'application/octet-stream';
        $size = (int)($_FILES['comprovante']['size'] ?? 0);

        $targetDirectory = rtrim(UPLOADS_PATH, '/') . '/fines';
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $filename = uniqid('fine_', true) . ($ext !== '' ? '.' . $ext : '');
        $relativePath = '/fines/' . $filename;
        $target = $targetDirectory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            flash('error', 'Não foi possível salvar o comprovante.');
            $this->redirect('/fines');
        }

        if (!empty($fine['comprovante_path'])) {
            $oldPath = rtrim(UPLOADS_PATH, '/') . '/' . ltrim((string)$fine['comprovante_path'], '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $fineModel->updateAttachment($id, [
            'comprovante_path' => $relativePath,
            'comprovante_nome_original' => $original,
            'comprovante_mime_type' => $mime,
            'comprovante_tamanho_bytes' => $size,
        ]);

        flash('success', 'Comprovante atualizado com sucesso.');
        $this->redirect('/fines');
    }

    public function viewAttachment(): void
    {
        $this->outputAttachment(false);
    }

    public function downloadAttachment(): void
    {
        $this->outputAttachment(true);
    }

    private function outputAttachment(bool $download): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $fine = (new TrafficFine())->find($id);
        if (!$fine || empty($fine['comprovante_path'])) {
            http_response_code(404);
            exit('Comprovante não encontrado');
        }

        $fullPath = rtrim(UPLOADS_PATH, '/') . '/' . ltrim((string)$fine['comprovante_path'], '/');
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('Arquivo não encontrado');
        }

        $mime = (string)($fine['comprovante_mime_type'] ?? 'application/octet-stream');
        $filename = (string)($fine['comprovante_nome_original'] ?? basename($fullPath));

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . addslashes($filename) . '"');
        readfile($fullPath);
        exit;
    }

    private function finePayload(bool $isUpdate): array
    {
        $rentalId = (int)($_POST['rental_id'] ?? 0);
        if ($rentalId <= 0) {
            flash('error', 'A multa deve estar vinculada a uma locação.');
            $this->redirect('/fines');
        }

        $rental = (new Rental())->find($rentalId);
        if (!$rental) {
            flash('error', 'Locação vinculada não encontrada.');
            $this->redirect('/fines');
        }

        $auto = trim((string)($_POST['auto_infracao'] ?? ''));
        $local = trim((string)($_POST['local_infracao'] ?? ''));
        $valor = (float)($_POST['valor'] ?? 0);
        $dataHora = (string)($_POST['data_hora_multa'] ?? '');
        $vencimento = (string)($_POST['data_vencimento'] ?? '');

        if ($auto === '' || $local === '' || $valor <= 0 || $dataHora === '' || $vencimento === '') {
            flash('error', 'Preencha os campos obrigatórios da multa.');
            $this->redirect('/fines');
        }

        return [
            'rental_id' => $rentalId,
            'auto_infracao' => $auto,
            'local_infracao' => $local,
            'valor' => $valor,
            'data_hora_multa' => $dataHora,
            'data_vencimento' => $vencimento,
            'observacoes' => trim((string)($_POST['observacoes'] ?? '')),
            'status' => $isUpdate
                ? $this->sanitizeStatus((string)($_POST['status'] ?? TrafficFine::STATUS_PENDENTE))
                : TrafficFine::STATUS_PENDENTE,
            'gerar_despesa_financeiro' => !empty($_POST['gerar_despesa_financeiro']) ? 1 : 0,
        ];
    }

    private function syncFinancial(?array $fine): void
    {
        if (!$fine) {
            return;
        }

        $financialModel = new FinancialEntry();
        if ((int)$fine['gerar_despesa_financeiro'] === 1) {
            $financialModel->syncFineExpense($fine);
            return;
        }

        $financialModel->deleteByFineId((int)$fine['id']);
    }

    private function sanitizeStatus(string $status): string
    {
        $allowed = array_keys($this->statusOptions());
        return in_array($status, $allowed, true) ? $status : TrafficFine::STATUS_PENDENTE;
    }

    private function statusOptions(): array
    {
        return [
            TrafficFine::STATUS_PENDENTE => 'Pendente',
            TrafficFine::STATUS_PAGA => 'Paga',
            TrafficFine::STATUS_VENCIDA => 'Vencida',
            TrafficFine::STATUS_CANCELADA => 'Cancelada',
        ];
    }
}
