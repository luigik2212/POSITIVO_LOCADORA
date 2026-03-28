<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialEntry;
use App\Models\Maintenance;
use App\Models\Rental;
use App\Models\Vehicle;

class ReportController extends Controller
{
    public function index(): void
    {
        $vehicleModel = new Vehicle();
        $financialModel = new FinancialEntry();
        $maintenanceModel = new Maintenance();
        $rentalModel = new Rental();

        $financialModel->generateWeeklyRentalCharges();
        $financialModel->generateRecurringEntries();

        $vehicleSearch = trim((string)($_GET['vehicle_search'] ?? ''));
        $vehicleId = !empty($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');

        $periodType = ($_GET['period_type'] ?? 'month') === 'year' ? 'year' : 'month';
        $year = !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = $periodType === 'month'
            ? (!empty($_GET['month']) ? (int)$_GET['month'] : (int)date('n'))
            : null;

        $vehicles = $vehicleModel->all($vehicleSearch !== '' ? $vehicleSearch : null);
        $selectedVehicle = $vehicleId ? $vehicleModel->find($vehicleId) : null;

        $vehicleReport = [
            'receitas' => 0.0,
            'gastos_manutencao' => 0.0,
            'saldo' => 0.0,
            'qtd_locacoes' => 0,
            'qtd_ativas' => 0,
            'qtd_finalizadas' => 0,
            'qtd_canceladas' => 0,
        ];

        if ($vehicleId) {
            $vehicleReport['receitas'] = $financialModel->sumByVehicleAndType($vehicleId, 'receita', $from, $to);
            $vehicleReport['gastos_manutencao'] = $maintenanceModel->totalSpentByVehicle($vehicleId, $from, $to);
            $vehicleReport['saldo'] = $vehicleReport['receitas'] - $vehicleReport['gastos_manutencao'];

            $rentalCounters = $rentalModel->countByVehicle($vehicleId, $from, $to);
            $vehicleReport['qtd_locacoes'] = (int)($rentalCounters['total'] ?? 0);
            $vehicleReport['qtd_ativas'] = (int)($rentalCounters['ativas'] ?? 0);
            $vehicleReport['qtd_finalizadas'] = (int)($rentalCounters['finalizadas'] ?? 0);
            $vehicleReport['qtd_canceladas'] = (int)($rentalCounters['canceladas'] ?? 0);
        }

        $financialSummary = $financialModel->summaryByPeriod($month, $year, $periodType);
        $financialEvolution = $financialModel->monthlySummaryByYear($year);

        $this->view('reports/index', [
            'vehicles' => $vehicles,
            'vehicleSearch' => $vehicleSearch,
            'vehicleId' => $vehicleId,
            'selectedVehicle' => $selectedVehicle,
            'vehicleReport' => $vehicleReport,
            'from' => $from,
            'to' => $to,
            'periodType' => $periodType,
            'month' => $month,
            'year' => $year,
            'financialSummary' => $financialSummary,
            'financialEvolution' => $financialEvolution,
        ]);
    }
}
