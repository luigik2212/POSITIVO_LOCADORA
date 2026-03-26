<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MileageHistory;
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

    public function mileageHistory(): void
    {
        $this->view('vehicles/mileage-history', [
            'history' => (new MileageHistory())->all(),
        ]);
    }

    public function store(): void
    {
        validateCsrf();
        $data = $this->vehiclePayload();
        if (!$this->validateRequired($data)) {
            $this->redirect('/vehicles');
        }

        (new Vehicle())->create($data);
        flash('success', 'Veículo cadastrado com sucesso.');
        $this->redirect('/vehicles');
    }

    public function update(): void
    {
        validateCsrf();
        $vehicleModel = new Vehicle();

        $data = $this->vehiclePayload();
        if (!$this->validateRequired($data)) {
            $this->redirect('/vehicles');
        }

        $data['id'] = (int)($_POST['id'] ?? 0);
        $before = $vehicleModel->find($data['id']);

        $vehicleModel->update($data);

        if ($before && (int)$before['quilometragem_atual'] !== (int)$data['quilometragem_atual']) {
            (new MileageHistory())->create(
                $data['id'],
                (int)$before['quilometragem_atual'],
                (int)$data['quilometragem_atual'],
                'edicao_manual'
            );
        }

        flash('success', 'Veículo atualizado com sucesso.');
        $this->redirect('/vehicles');
    }

    public function delete(): void
    {
        validateCsrf();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            flash('error', 'Veículo inválido para exclusão.');
            $this->redirect('/vehicles');
        }

        try {
            (new Vehicle())->delete($id);
            flash('success', 'Veículo excluído com sucesso.');
        } catch (\Throwable $e) {
            flash('error', 'Não foi possível excluir este veículo, pois ele possui vínculos no sistema.');
        }

        $this->redirect('/vehicles');
    }

    public function updateMileage(): void
    {
        validateCsrf();
        $vehicleId = (int)$_POST['id'];
        $kmNovo = (int)$_POST['quilometragem_atual'];

        $vehicleModel = new Vehicle();
        $before = $vehicleModel->find($vehicleId);
        $vehicleModel->updateMileage($vehicleId, $kmNovo);

        if ($before && (int)$before['quilometragem_atual'] !== $kmNovo) {
            (new MileageHistory())->create($vehicleId, (int)$before['quilometragem_atual'], $kmNovo, 'edicao_manual');
        }

        flash('success', 'Quilometragem atualizada.');
        $this->redirect('/vehicles');
    }

    private function vehiclePayload(): array
    {
        return [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'marca' => trim((string)($_POST['marca'] ?? '')),
            'modelo' => trim((string)($_POST['modelo'] ?? '')),
            'ano' => (int)($_POST['ano'] ?? 0),
            'placa' => strtoupper(trim((string)($_POST['placa'] ?? ''))),
            'renavam' => trim((string)($_POST['renavam'] ?? '')),
            'cor' => trim((string)($_POST['cor'] ?? '')),
            'quilometragem_atual' => (int)($_POST['quilometragem_atual'] ?? 0),
            'categoria' => trim((string)($_POST['categoria'] ?? '')),
            'valor_diaria' => (float)($_POST['valor_diaria'] ?? 0),
            'valor_semanal' => (float)($_POST['valor_semanal'] ?? 0),
            'valor_mensal' => (float)($_POST['valor_mensal'] ?? 0),
            'status' => $_POST['status'] ?? 'disponivel',
            'observacoes' => trim((string)($_POST['observacoes'] ?? '')),
        ];
    }

    private function validateRequired(array $data): bool
    {
        $required = [
            'nome' => 'Nome',
            'placa' => 'Placa',
            'quilometragem_atual' => 'KM',
            'valor_diaria' => 'Valor diária',
            'valor_semanal' => 'Valor semanal',
            'valor_mensal' => 'Valor mensal',
        ];

        foreach ($required as $key => $label) {
            if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
                flash('error', "Campo obrigatório não informado: {$label}.");
                return false;
            }
        }

        return true;
    }
}
