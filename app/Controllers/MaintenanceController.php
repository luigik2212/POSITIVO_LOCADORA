<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\FinancialEntry;

class MaintenanceController extends Controller
{
    public function index(): void
    {
        $maintenanceModel = new Maintenance();
        $vehicleModel = new Vehicle();
        $this->view('maintenances/index', [
            'maintenances' => $maintenanceModel->all(isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null),
            'vehicles' => $vehicleModel->all(),
            'totals' => $maintenanceModel->totalByVehicle(),
        ]);
    }

    public function store(): void
    {
        validateCsrf();
        $data = [
            'vehicle_id' => (int)$_POST['vehicle_id'],
            'tipo_manutencao' => trim($_POST['tipo_manutencao']),
            'descricao' => trim($_POST['descricao']),
            'data_manutencao' => $_POST['data_manutencao'],
            'quilometragem_manutencao' => (int)$_POST['quilometragem_manutencao'],
            'valor_gasto' => (float)$_POST['valor_gasto'],
            'oficina_fornecedor' => trim($_POST['oficina_fornecedor']),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'status' => $_POST['status'],
        ];

        $maintenanceModel = new Maintenance();
        $maintenanceModel->create($data);

        $vehicleModel = new Vehicle();
        if ($data['status'] === 'pendente') {
            $vehicleModel->setStatus($data['vehicle_id'], 'manutencao');
        }

        (new FinancialEntry())->create([
            'tipo' => 'despesa',
            'categoria' => 'manutencao',
            'descricao' => 'Manutenção veículo #' . $data['vehicle_id'],
            'valor' => $data['valor_gasto'],
            'data_movimentacao' => $data['data_manutencao'],
            'rental_id' => null,
            'maintenance_id' => null,
            'vehicle_id' => $data['vehicle_id'],
            'client_id' => null,
        ]);

        flash('success', 'Manutenção registrada.');
        $this->redirect('/maintenances');
    }

    public function updateStatus(): void
    {
        validateCsrf();
        $id = (int)$_POST['id'];
        $status = $_POST['status'];

        $maintenanceModel = new Maintenance();
        $maintenance = $maintenanceModel->find($id);
        if ($maintenance) {
            $maintenanceModel->updateStatus($id, $status);
            if ($status === 'concluida') {
                (new Vehicle())->setStatus((int)$maintenance['vehicle_id'], 'disponivel');
            }
        }

        flash('success', 'Status de manutenção atualizado.');
        $this->redirect('/maintenances');
    }
}
