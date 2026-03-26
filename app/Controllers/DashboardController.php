<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Maintenance;
use App\Models\FinancialEntry;

class DashboardController extends Controller
{
    public function index(): void
    {
        $vehicleModel = new Vehicle();
        $clientModel = new Client();
        $rentalModel = new Rental();
        $maintenanceModel = new Maintenance();
        $financialModel = new FinancialEntry();

        $this->view('dashboard/index', [
            'vehicleCounters' => $vehicleModel->counters(),
            'totalClients' => $clientModel->count(),
            'activeContracts' => $rentalModel->activeCount(),
            'monthFinancial' => $financialModel->monthSummary(),
            'latestRentals' => $rentalModel->latest(),
            'pendingMaintenances' => $maintenanceModel->pending(),
            'upcomingRentals' => $rentalModel->upcoming(),
        ]);
    }
}
