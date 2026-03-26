<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Rental;

class ClientController extends Controller
{
    public function index(): void
    {
        $clientModel = new Client();
        $rentalModel = new Rental();

        $clients = $clientModel->all($_GET['search'] ?? null);
        $selectedClient = isset($_GET['client_id']) ? $clientModel->find((int)$_GET['client_id']) : null;
        $history = $selectedClient ? $rentalModel->all(['client_id' => (int)$selectedClient['id']]) : [];

        $clientIds = array_map(static fn(array $client): int => (int)$client['id'], $clients);
        $documentsByClient = (new ClientDocument())->groupedByClientIds($clientIds);

        $this->view('clients/index', [
            'clients' => $clients,
            'selectedClient' => $selectedClient,
            'history' => $history,
            'documentsByClient' => $documentsByClient,
        ]);
    }

    public function store(): void
    {
        validateCsrf();
        $clientModel = new Client();
        $clientId = $clientModel->create($this->payload());
        $this->saveDocuments($clientId);

        flash('success', 'Cliente cadastrado com sucesso.');
        $this->redirect('/clients');
    }

    public function update(): void
    {
        validateCsrf();
        $payload = $this->payload();
        $payload['id'] = (int)$_POST['id'];
        (new Client())->update($payload);
        $this->saveDocuments($payload['id']);

        flash('success', 'Cliente atualizado com sucesso.');
        $this->redirect('/clients');
    }

    public function downloadDocument(): void
    {
        $clientId = (int)($_GET['client_id'] ?? 0);
        $documentId = (int)($_GET['document_id'] ?? 0);

        if ($clientId <= 0 || $documentId <= 0) {
            http_response_code(404);
            exit('Documento não encontrado.');
        }

        $document = (new ClientDocument())->findForClient($documentId, $clientId);
        if (!$document) {
            http_response_code(404);
            exit('Documento não encontrado.');
        }

        $filePath = APP_ROOT . $document['caminho_arquivo'];
        if (!is_file($filePath)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        header('Content-Type: ' . ($document['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string)filesize($filePath));
        header('Content-Disposition: attachment; filename="' . basename($document['nome_original']) . '"');
        readfile($filePath);
        exit;
    }

    private function payload(): array
    {
        return [
            'nome_completo' => trim($_POST['nome_completo']),
            'cpf' => trim($_POST['cpf']),
            'rg' => trim($_POST['rg']),
            'cnh' => trim($_POST['cnh']),
            'validade_cnh' => $_POST['validade_cnh'] ?: null,
            'telefone' => trim($_POST['telefone']),
            'email' => trim($_POST['email']),
            'endereco_completo' => trim($_POST['endereco_completo']),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
    }

    private function saveDocuments(int $clientId): void
    {
        if (!isset($_FILES['documentos'])) {
            return;
        }

        $targetDirectory = rtrim(UPLOADS_PATH, '/') . '/client-documents';
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $documents = new ClientDocument();

        foreach ($_FILES['documentos']['tmp_name'] as $index => $tmpName) {
            if (!is_uploaded_file($tmpName)) {
                continue;
            }

            $originalName = (string)$_FILES['documentos']['name'][$index];
            $mimeType = mime_content_type($tmpName) ?: 'application/octet-stream';
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = uniqid('doc_', true) . ($extension ? ".{$extension}" : '');
            $targetPath = $targetDirectory . '/' . $filename;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                continue;
            }

            $documents->create([
                'client_id' => $clientId,
                'nome_original' => $originalName,
                'caminho_arquivo' => '/uploads/client-documents/' . $filename,
                'mime_type' => $mimeType,
                'tamanho_bytes' => (int)($_FILES['documentos']['size'][$index] ?? 0),
            ]);
        }
    }
}
