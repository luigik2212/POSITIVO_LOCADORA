<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialEntry;
use App\Models\Maintenance;
use App\Models\MileageHistory;
use App\Models\Vehicle;

class MaintenanceController extends Controller
{
    public function index(): void
    {
        $maintenanceModel = new Maintenance();
        $vehicleModel = new Vehicle();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $vehicleId = !empty($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
        $pagination = $maintenanceModel->paginate($vehicleId, $page, $perPage);
        $totalPages = max(1, (int)ceil(($pagination['total'] ?? 0) / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $pagination = $maintenanceModel->paginate($vehicleId, $page, $perPage);
        }

        $this->view('maintenances/index', [
            'maintenances' => $pagination['data'],
            'vehicles' => $vehicleModel->all(),
            'totals' => $maintenanceModel->totalByVehicle(),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'queryParams' => [
                'vehicle_id' => $vehicleId,
            ],
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
        $vehicle = $vehicleModel->find($data['vehicle_id']);
        if ($vehicle && (int)$vehicle['quilometragem_atual'] !== $data['quilometragem_manutencao']) {
            $vehicleModel->updateMileage($data['vehicle_id'], $data['quilometragem_manutencao']);
            (new MileageHistory())->create(
                $data['vehicle_id'],
                (int)$vehicle['quilometragem_atual'],
                $data['quilometragem_manutencao'],
                'manutencao'
            );
        }

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

    public function report(): void
    {
        $maintenanceModel = new Maintenance();
        $vehicleModel = new Vehicle();
        $vehicleId = !empty($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $rows = $maintenanceModel->report($vehicleId, $from, $to);

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float)$row['valor_gasto'];
        }

        $this->view('maintenances/report', [
            'maintenances' => $rows,
            'vehicles' => $vehicleModel->all(),
            'vehicleId' => $vehicleId,
            'from' => $from,
            'to' => $to,
            'total' => $total,
        ]);
    }
}
