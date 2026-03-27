<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialEntry;
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
        $status = ($_POST['pagamento_status'] ?? 'nao_pago') === 'pago' ? 'pago' : 'nao_pago';
        (new FinancialEntry())->updatePaymentStatus((int)($_POST['id'] ?? 0), $status);
        flash('success', 'Status de pagamento atualizado.');
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
}
