<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function index(): void
    {
        $vehicleModel = new Vehicle();
        $this->view('vehicles/index', [
            'vehicles' => $vehicleModel->all($_GET['search'] ?? null, $_GET['status'] ?? null),
        ]);
    }

    public function store(): void
    {
        validateCsrf();
        $data = $this->vehiclePayload();
        (new Vehicle())->create($data);
        flash('success', 'Veículo cadastrado com sucesso.');
        $this->redirect('/vehicles');
    }

    public function update(): void
    {
        validateCsrf();
        $data = $this->vehiclePayload();
        $data['id'] = (int)($_POST['id'] ?? 0);
        (new Vehicle())->update($data);
        flash('success', 'Veículo atualizado com sucesso.');
        $this->redirect('/vehicles');
    }

    public function inactivate(): void
    {
        validateCsrf();
        (new Vehicle())->setStatus((int)$_POST['id'], 'inativo');
        flash('success', 'Veículo inativado.');
        $this->redirect('/vehicles');
    }

    public function updateMileage(): void
    {
        validateCsrf();
        (new Vehicle())->updateMileage((int)$_POST['id'], (int)$_POST['quilometragem_atual']);
        flash('success', 'Quilometragem atualizada.');
        $this->redirect('/vehicles');
    }

    private function vehiclePayload(): array
    {
        return [
            'nome' => trim($_POST['nome']),
            'marca' => trim($_POST['marca']),
            'modelo' => trim($_POST['modelo']),
            'ano' => (int)$_POST['ano'],
            'placa' => strtoupper(trim($_POST['placa'])),
            'renavam' => trim($_POST['renavam']),
            'cor' => trim($_POST['cor']),
            'quilometragem_atual' => (int)$_POST['quilometragem_atual'],
            'categoria' => trim($_POST['categoria']),
            'valor_diaria' => (float)$_POST['valor_diaria'],
            'valor_semanal' => (float)$_POST['valor_semanal'],
            'valor_mensal' => (float)$_POST['valor_mensal'],
            'status' => $_POST['status'] ?? 'disponivel',
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
    }
}
