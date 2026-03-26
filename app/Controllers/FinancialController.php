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
        (new FinancialEntry())->create([
            'tipo' => $_POST['tipo'],
            'categoria' => $_POST['categoria'],
            'descricao' => trim($_POST['descricao']),
            'valor' => (float)$_POST['valor'],
            'data_movimentacao' => $_POST['data_movimentacao'],
            'rental_id' => !empty($_POST['rental_id']) ? (int)$_POST['rental_id'] : null,
            'maintenance_id' => !empty($_POST['maintenance_id']) ? (int)$_POST['maintenance_id'] : null,
            'vehicle_id' => !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null,
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
        ]);

        flash('success', 'Movimentação financeira cadastrada.');
        $this->redirect('/financial');
    }
}
