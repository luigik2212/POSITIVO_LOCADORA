<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialEntry;

class FinancialController extends Controller
{
    public function index(): void
    {
        $financial = new FinancialEntry();
        $financial->generateWeeklyRentalCharges();
        $financial->generateRecurringEntries();

        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $entries = $financial->all($from, $to);

        $totals = ['receitas' => 0.0, 'despesas' => 0.0, 'lucro' => 0.0];
        foreach ($entries as $entry) {
            if ($entry['tipo'] === 'receita') {
                $totals['receitas'] += (float)$entry['valor'];
            } else {
                $totals['despesas'] += (float)$entry['valor'];
            }
        }
        $totals['lucro'] = $totals['receitas'] - $totals['despesas'];

        $this->view('financial/index', compact('entries', 'totals', 'from', 'to'));
    }

    public function store(): void
    {
        validateCsrf();
        (new FinancialEntry())->create($this->payload());

        flash('success', 'Movimentação financeira cadastrada.');
        $this->redirect('/financial');
    }

    public function update(): void
    {
        validateCsrf();
        $payload = $this->payload();
        $payload['id'] = (int)($_POST['id'] ?? 0);
        (new FinancialEntry())->update($payload);
        flash('success', 'Movimentação financeira atualizada.');
        $this->redirect('/financial');
    }

    public function delete(): void
    {
        validateCsrf();
        (new FinancialEntry())->delete((int)($_POST['id'] ?? 0));
        flash('success', 'Movimentação financeira excluída.');
        $this->redirect('/financial');
    }

    public function updatePaymentStatus(): void
    {
        validateCsrf();
        $status = ($_POST['pagamento_status'] ?? 'nao_pago') === 'pago' ? 'pago' : 'nao_pago';
        (new FinancialEntry())->updatePaymentStatus((int)($_POST['id'] ?? 0), $status);
        flash('success', 'Status de pagamento atualizado.');
        $this->redirect('/financial');
    }

    private function payload(): array
    {
        $isRecurring = isset($_POST['recorrente']) && $_POST['recorrente'] === '1';

        return [
            'tipo' => $_POST['tipo'],
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
}
