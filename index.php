<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/app/Core/bootstrap.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\VehicleController;
use App\Controllers\ClientController;
use App\Controllers\RentalController;
use App\Controllers\MaintenanceController;
use App\Controllers\FinancialController;

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index'], true);

$router->get('/vehicles', [VehicleController::class, 'index'], true);
$router->post('/vehicles/store', [VehicleController::class, 'store'], true);
$router->post('/vehicles/update', [VehicleController::class, 'update'], true);
$router->post('/vehicles/delete', [VehicleController::class, 'delete'], true);
$router->post('/vehicles/update-mileage', [VehicleController::class, 'updateMileage'], true);
$router->get('/vehicles/mileage-history', [VehicleController::class, 'mileageHistory'], true);

$router->get('/clients', [ClientController::class, 'index'], true);
$router->post('/clients/store', [ClientController::class, 'store'], true);
$router->post('/clients/update', [ClientController::class, 'update'], true);
$router->get('/clients/document-download', [ClientController::class, 'downloadDocument'], true);

$router->get('/rentals', [RentalController::class, 'index'], true);
$router->post('/rentals/store', [RentalController::class, 'store'], true);
$router->post('/rentals/finalize', [RentalController::class, 'finalize'], true);
$router->post('/rentals/cancel', [RentalController::class, 'cancel'], true);

$router->get('/maintenances', [MaintenanceController::class, 'index'], true);
$router->post('/maintenances/store', [MaintenanceController::class, 'store'], true);
$router->post('/maintenances/update-status', [MaintenanceController::class, 'updateStatus'], true);

$router->get('/financial', [FinancialController::class, 'index'], true);
$router->post('/financial/store', [FinancialController::class, 'store'], true);
$router->post('/financial/update', [FinancialController::class, 'update'], true);
$router->post('/financial/delete', [FinancialController::class, 'delete'], true);
$router->post('/financial/payment-status', [FinancialController::class, 'updatePaymentStatus'], true);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
