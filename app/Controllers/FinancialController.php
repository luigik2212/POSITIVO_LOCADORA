<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialEntry;
use App\Models\MileageHistory;
use App\Models\NotificationState;
use App\Models\Vehicle;

class FinancialController extends Controller
{
    public function index(): void
    {
        $financial = new FinancialEntry();
        $financial->generateWeeklyRentalCharges();
        $financial->generateRecurringEntries();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $tab = ($_GET['tab'] ?? 'payable') === 'receivable' ? 'receivable' : 'payable';
        $tipo = $tab === 'receivable' ? 'receita' : 'despesa';

        $entries = $financial->all($from, $to, $tipo, true);
        $vehicles = (new Vehicle())->all();
        $totals = ['total' => 0.0, 'paid' => 0.0];
        foreach ($entries as $entry) {
            $value = (float)$entry['valor'];
            $totals['total'] += $value;
            if (($entry['pagamento_status'] ?? 'nao_pago') === 'pago') {
                $totals['paid'] += $value;
            }
        }

        $this->view('financial/index', compact('entries', 'totals', 'from', 'to', 'tab', 'vehicles'));
    }

    public function store(): void
    {
        validateCsrf();
        (new FinancialEntry())->create($this->payload());

        flash('success', 'Movimentação financeira cadastrada.');
        $this->redirectWithFilters();
    }

    public function update(): void
    {
        validateCsrf();
        $payload = $this->payload();
        $payload['id'] = (int)($_POST['id'] ?? 0);
        (new FinancialEntry())->update($payload);
        flash('success', 'Movimentação financeira atualizada.');
        $this->redirectWithFilters();
    }

    public function delete(): void
    {
        validateCsrf();
        (new FinancialEntry())->delete((int)($_POST['id'] ?? 0));
        flash('success', 'Movimentação financeira excluída.');
        $this->redirectWithFilters();
    }

    public function updatePaymentStatus(): void
    {
        validateCsrf();
        $entryId = (int)($_POST['id'] ?? 0);
        $status = ($_POST['pagamento_status'] ?? 'nao_pago') === 'pago' ? 'pago' : 'nao_pago';

        $financialModel = new FinancialEntry();
        $entry = $financialModel->find($entryId);
        if (!$entry) {
            flash('error', 'Lançamento financeiro não encontrado.');
            $this->redirectWithFilters();
            return;
        }

        if ($status === 'pago' && $this->isWeeklyReceivableEntry($entry)) {
            $rawKm = trim((string)($_POST['quilometragem_atual'] ?? ''));
            $vehicleId = (int)($entry['vehicle_id'] ?? 0);
            $vehicle = $vehicleId > 0 ? (new Vehicle())->find($vehicleId) : null;
            if (!$vehicle) {
                flash('error', 'Não foi possível vincular a cobrança a um veículo para atualizar o KM.');
                $this->redirectWithFilters();
                return;
            }

            $allowPendingKm = ($_POST['permitir_preencher_km_depois'] ?? '0') === '1';
            if ($rawKm === '' || !ctype_digit($rawKm)) {
                if (!$allowPendingKm) {
                    flash('error', 'Informe a quilometragem atual para baixar a cobrança semanal.');
                    $this->redirectWithFilters();
                    return;
                }

                $financialModel->markPendingMileageFill($entryId, true);
            } else {
                $kmNovo = (int)$rawKm;
                $kmAtual = (int)($vehicle['quilometragem_atual'] ?? 0);
                if ($kmNovo < $kmAtual) {
                    flash('error', 'KM informado é menor que o KM atual do veículo.');
                    $this->redirectWithFilters();
                    return;
                }

                if ($kmNovo > $kmAtual) {
                    $vehicleModel = new Vehicle();
                    $vehicleModel->updateMileage($vehicleId, $kmNovo);
                    (new MileageHistory())->create($vehicleId, $kmAtual, $kmNovo, 'baixa_pagamento_semanal');
                }
                $financialModel->markPendingMileageFill($entryId, false);
                $this->resolvePendingMileageNotification($entryId);
            }
        }

        if ($status !== 'pago' && $this->isWeeklyReceivableEntry($entry)) {
            $financialModel->markPendingMileageFill($entryId, false);
            $this->resolvePendingMileageNotification($entryId);
        }

        $financialModel->updatePaymentStatus($entryId, $status);
        flash('success', 'Status de pagamento atualizado.');
        $this->redirectWithFilters();
    }

    public function fillMissingMileage(): void
    {
        validateCsrf();
        $entryId = (int)($_POST['id'] ?? 0);
        $rawKm = trim((string)($_POST['quilometragem_atual'] ?? ''));

        if ($entryId <= 0 || $rawKm === '' || !ctype_digit($rawKm)) {
            flash('error', 'Informe um KM válido para concluir o preenchimento pendente.');
            $this->redirectWithFilters();
            return;
        }

        $financialModel = new FinancialEntry();
        $entry = $financialModel->find($entryId);
        if (!$entry || !$this->isWeeklyReceivableEntry($entry)) {
            flash('error', 'Lançamento inválido para preenchimento de KM.');
            $this->redirectWithFilters();
            return;
        }

        $vehicleId = (int)($entry['vehicle_id'] ?? 0);
        $vehicle = $vehicleId > 0 ? (new Vehicle())->find($vehicleId) : null;
        if (!$vehicle) {
            flash('error', 'Veículo não encontrado para atualizar KM.');
            $this->redirectWithFilters();
            return;
        }

        $kmNovo = (int)$rawKm;
        $kmAtual = (int)($vehicle['quilometragem_atual'] ?? 0);
        if ($kmNovo < $kmAtual) {
            flash('error', 'KM informado é menor que o KM atual do veículo.');
            $this->redirectWithFilters();
            return;
        }

        if ($kmNovo > $kmAtual) {
            $vehicleModel = new Vehicle();
            $vehicleModel->updateMileage($vehicleId, $kmNovo);
            (new MileageHistory())->create($vehicleId, $kmAtual, $kmNovo, 'baixa_pagamento_semanal');
        }

        $financialModel->markPendingMileageFill($entryId, false);
        $this->resolvePendingMileageNotification($entryId);

        flash('success', 'KM do veículo preenchido com sucesso.');
        $this->redirectWithFilters();
    }

    public function report(): void
    {
        $financial = new FinancialEntry();
        $financial->generateWeeklyRentalCharges();
        $financial->generateRecurringEntries();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $report = $financial->report($from, $to);

        $this->view('financial/report', [
            'entries' => $report['entries'],
            'summary' => $report['summary'],
            'from' => $from,
            'to' => $to,
        ]);
    }


    private function isWeeklyReceivableEntry(array $entry): bool
    {
        return ($entry['tipo'] ?? '') === 'receita'
            && ($entry['categoria'] ?? '') === 'locacao_semanal'
            && ($entry['rental_tipo_cobranca'] ?? '') === 'semanal';
    }

    private function payload(): array
    {
        $isRecurring = isset($_POST['recorrente']) && $_POST['recorrente'] === '1';
        $tab = ($_POST['tab'] ?? $_GET['tab'] ?? 'payable') === 'receivable' ? 'receivable' : 'payable';
        $tipo = $tab === 'receivable' ? 'receita' : 'despesa';

        return [
            'tipo' => $tipo,
            'categoria' => trim((string)$_POST['categoria']),
            'descricao' => trim((string)$_POST['descricao']),
            'valor' => (float)$_POST['valor'],
            'data_movimentacao' => $_POST['data_movimentacao'],
            'rental_id' => !empty($_POST['rental_id']) ? (int)$_POST['rental_id'] : null,
            'maintenance_id' => !empty($_POST['maintenance_id']) ? (int)$_POST['maintenance_id'] : null,
            'vehicle_id' => !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null,
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'pagamento_status' => ($_POST['pagamento_status'] ?? 'nao_pago') === 'pago' ? 'pago' : 'nao_pago',
            'recorrente' => $isRecurring,
            'recorrencia_periodo' => $isRecurring ? ($_POST['recorrencia_periodo'] ?? 'mensal') : null,
            'referencia_data' => $_POST['data_movimentacao'],
        ];
    }

    private function redirectWithFilters(): void
    {
        $tab = ($_POST['tab'] ?? 'payable') === 'receivable' ? 'receivable' : 'payable';
        $from = $_POST['from'] ?? date('Y-m-01');
        $to = $_POST['to'] ?? date('Y-m-t');

        $query = http_build_query([
            'tab' => $tab,
            'from' => $from,
            'to' => $to,
        ]);
        $this->redirect('/financial?' . $query);
    }

    private function resolvePendingMileageNotification(int $entryId): void
    {
        $user = authUser();
        if (!$user) {
            return;
        }

        $key = 'pending-km-entry-' . $entryId;
        (new NotificationState())->markResolved((int)$user['id'], $key);
    }
}
