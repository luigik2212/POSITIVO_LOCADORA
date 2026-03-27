<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Checklist;
use App\Models\Client;
use App\Models\FinancialEntry;
use App\Models\MileageHistory;
use App\Models\Rental;
use App\Models\Vehicle;
use App\Services\RentalAlertService;

class RentalController extends Controller
{
    public function index(): void
    {
        $rentalModel = new Rental();
        $clientModel = new Client();
        $vehicleModel = new Vehicle();

        $status = $_GET['status'] ?? 'ativa';
        $filters = [
            'status' => $status,
            'billing_type' => $_GET['billing_type'] ?? null,
            'client_id' => $_GET['client_id'] ?? null,
            'vehicle_id' => $_GET['vehicle_id'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];

        $this->view('rentals/index', [
            'rentals' => $rentalModel->all($filters),
            'clients' => $clientModel->all(),
            'vehicles' => $vehicleModel->available(),
            'allVehicles' => $vehicleModel->all(),
            'filters' => $filters,
        ]);
    }

    public function store(): void
    {
        validateCsrf();

        $vehicleModel = new Vehicle();
        $vehicleId = (int)$_POST['vehicle_id'];
        $vehicle = $vehicleModel->find($vehicleId);

        if (!$vehicle || in_array($vehicle['status'], ['manutencao', 'inativo'], true)) {
            flash('error', 'Veículo indisponível para locação.');
            $this->redirect('/rentals');
        }

        $tipo = $_POST['tipo_cobranca'];
        $valorCobranca = match ($tipo) {
            'diaria' => (float)$vehicle['valor_diaria'],
            'semanal' => (float)$vehicle['valor_semanal'],
            'mensal' => (float)$vehicle['valor_mensal'],
            default => 0,
        };

        $tempo = max(1, (int)$_POST['tempo_contrato']);
        $valorPrevisto = $valorCobranca * $tempo;

        $payload = [
            'client_id' => (int)$_POST['client_id'],
            'vehicle_id' => $vehicleId,
            'tipo_cobranca' => $tipo,
            'valor_cobranca' => $valorCobranca,
            'tempo_contrato' => $tempo,
            'dia_semana_vencimento' => $tipo === 'semanal' ? ($_POST['dia_semana_vencimento'] ?? null) : null,
            'data_inicio' => $_POST['data_inicio'],
            'data_prevista_termino' => $_POST['data_prevista_termino'],
            'quilometragem_saida' => (int)$_POST['quilometragem_saida'],
            'caucao' => (float)$_POST['caucao'],
            'valor_total_previsto' => $valorPrevisto,
            'status' => 'ativa',
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];

        $rentalModel = new Rental();
        $rentalId = $rentalModel->create($payload);
        $vehicleModel->setStatus($vehicleId, 'alugado');

        $this->saveChecklist($rentalId, 'entrega');

        $financialEntry = new FinancialEntry();
        if ($tipo === 'semanal') {
            $financialEntry->generateWeeklyRentalChargesByRental(
                [
                    ...$payload,
                    'id' => $rentalId,
                ],
                true
            );
        } else {
            $financialEntry->create([
                'tipo' => 'receita',
                'categoria' => 'locacao',
                'descricao' => 'Receita prevista locação #' . $rentalId,
                'valor' => $valorPrevisto,
                'data_movimentacao' => $payload['data_inicio'],
                'rental_id' => $rentalId,
                'maintenance_id' => null,
                'vehicle_id' => $vehicleId,
                'client_id' => $payload['client_id'],
            ]);
        }

        flash('success', 'Locação criada com sucesso.');
        $this->redirect('/rentals');
    }

    public function finalize(): void
    {
        validateCsrf();
        $rentalId = (int)$_POST['id'];
        $rentalModel = new Rental();
        $rental = $rentalModel->find($rentalId);

        if (!$rental) {
            flash('error', 'Locação não encontrada.');
            $this->redirect('/rentals');
        }
        if (($rental['status'] ?? '') !== 'ativa') {
            flash('error', 'Somente locações ativas podem ser devolvidas.');
            $this->redirect('/rentals');
        }

        $kmRetorno = (int)$_POST['quilometragem_retorno'];
        $valorFinal = (float)($_POST['valor_total_final'] ?? $rental['valor_total_previsto']);

        $rentalModel->finalize([
            'id' => $rentalId,
            'data_real_termino' => $_POST['data_real_termino'],
            'quilometragem_retorno' => $kmRetorno,
            'valor_total_final' => $valorFinal,
            'obs' => '\nDevolução registrada em ' . date('d/m/Y H:i'),
        ]);

        $statusVeiculo = $_POST['retornar_para_manutencao'] === '1' ? 'manutencao' : 'disponivel';
        $vehicleModel = new Vehicle();
        $vehicleBefore = $vehicleModel->find((int)$rental['vehicle_id']);
        if ($statusVeiculo === 'manutencao' || !$rentalModel->hasActiveByVehicle((int)$rental['vehicle_id'], $rentalId)) {
            $vehicleModel->setStatus((int)$rental['vehicle_id'], $statusVeiculo);
        }

        if ($vehicleBefore && (int)$vehicleBefore['quilometragem_atual'] !== $kmRetorno) {
            (new MileageHistory())->create((int)$rental['vehicle_id'], (int)$vehicleBefore['quilometragem_atual'], $kmRetorno, 'devolucao');
        }
        $vehicleModel->updateMileage((int)$rental['vehicle_id'], $kmRetorno);

        $this->saveChecklist($rentalId, 'devolucao');

        flash('success', 'Locação finalizada.');
        $this->redirect('/rentals');
    }

    public function cancel(): void
    {
        validateCsrf();
        $id = (int)$_POST['id'];
        $rentalModel = new Rental();
        $rental = $rentalModel->find($id);

        if ($rental && ($rental['status'] ?? '') === 'ativa') {
            $rentalModel->cancel($id);
            if (!$rentalModel->hasActiveByVehicle((int)$rental['vehicle_id'], $id)) {
                (new Vehicle())->setStatus((int)$rental['vehicle_id'], 'disponivel');
            }
            flash('success', 'Locação cancelada.');
            $this->redirect('/rentals');
        }

        flash('error', 'Locação inválida para cancelamento.');
        $this->redirect('/rentals');
    }


    public function sendDueAlert(): void
    {
        validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Locação inválida para envio de alerta.');
            $this->redirect('/rentals');
        }

        $result = (new RentalAlertService())->sendManual($id);
        if ($result['success']) {
            flash('success', 'Alerta de vencimento enviado via WhatsApp com sucesso.');
        } else {
            flash('error', 'Falha ao enviar alerta WhatsApp: ' . ($result['error'] ?? 'erro desconhecido'));
        }

        $this->redirect('/rentals');
    }

    private function saveChecklist(int $rentalId, string $tipo): void
    {
        if (empty($_POST['checklist_' . $tipo . '_lataria'])) {
            return;
        }

        $checklistModel = new Checklist();
        $checklistId = $checklistModel->create([
            'rental_id' => $rentalId,
            'tipo_checklist' => $tipo,
            'lataria' => $_POST['checklist_' . $tipo . '_lataria'],
            'pneus' => $_POST['checklist_' . $tipo . '_pneus'],
            'vidros' => $_POST['checklist_' . $tipo . '_vidros'],
            'combustivel' => $_POST['checklist_' . $tipo . '_combustivel'],
            'limpeza' => $_POST['checklist_' . $tipo . '_limpeza'],
            'interior_estado' => $_POST['checklist_' . $tipo . '_interior'],
            'acessorios' => $_POST['checklist_' . $tipo . '_acessorios'],
            'avarias' => $_POST['checklist_' . $tipo . '_avarias'],
            'observacoes' => $_POST['checklist_' . $tipo . '_observacoes'] ?? null,
        ]);

        $inputName = 'anexos_' . $tipo;
        if (!isset($_FILES[$inputName])) {
            return;
        }

        foreach ($_FILES[$inputName]['tmp_name'] as $idx => $tmpName) {
            if (!is_uploaded_file($tmpName)) {
                continue;
            }

            $original = $_FILES[$inputName]['name'][$idx];
            $mime = mime_content_type($tmpName) ?: 'application/octet-stream';
            if (!preg_match('#^(image|video)/#', $mime)) {
                continue;
            }

            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $filename = uniqid('check_', true) . '.' . $ext;
            $relativePath = '/uploads/checklists/' . $filename;
            $targetDirectory = rtrim(UPLOADS_PATH, '/') . '/checklists';
            if (!is_dir($targetDirectory)) {
                mkdir($targetDirectory, 0775, true);
            }

            $target = $targetDirectory . '/' . $filename;
            move_uploaded_file($tmpName, $target);

            $checklistModel->addAttachment([
                'checklist_id' => $checklistId,
                'tipo_arquivo' => str_starts_with($mime, 'video/') ? 'video' : 'foto',
                'caminho_arquivo' => $relativePath,
            ]);
        }
    }
}
