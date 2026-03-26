<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Client;
use App\Models\Rental;

class ClientController extends Controller
{
    public function index(): void
    {
        $clientModel = new Client();
        $rentalModel = new Rental();

        $selectedClient = isset($_GET['client_id']) ? $clientModel->find((int)$_GET['client_id']) : null;
        $history = $selectedClient ? $rentalModel->all(['client_id' => (int)$selectedClient['id']]) : [];

        $this->view('clients/index', [
            'clients' => $clientModel->all($_GET['search'] ?? null),
            'selectedClient' => $selectedClient,
            'history' => $history,
        ]);
    }

    public function store(): void
    {
        validateCsrf();
        (new Client())->create($this->payload());
        flash('success', 'Cliente cadastrado com sucesso.');
        $this->redirect('/clients');
    }

    public function update(): void
    {
        validateCsrf();
        $payload = $this->payload();
        $payload['id'] = (int)$_POST['id'];
        (new Client())->update($payload);
        flash('success', 'Cliente atualizado com sucesso.');
        $this->redirect('/clients');
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
}
