<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Services\RentalAlertService;

$service = new RentalAlertService();
$stats = $service->processDueInSevenDays();

echo sprintf(
    "[%s] Alertas processados. Verificados: %d | Enviados: %d | Erros: %d\n",
    date('Y-m-d H:i:s'),
    $stats['checked'],
    $stats['sent'],
    $stats['errors']
);
