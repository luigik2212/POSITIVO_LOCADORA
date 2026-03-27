<?php require __DIR__ . '/../partials/header.php'; ?>

<?php
$alertLabel = static function (string $status): string {
  return match ($status) {
    'vence_em_7_dias' => 'Vence em 7 dias',
    'vence_hoje' => 'Vence hoje',
    'vencido' => 'Vencido',
    default => 'Sem alerta',
  };
};
?>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><h6>Total veículos</h6><h3><?= (int)$vehicleCounters['total'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><h6>Disponíveis</h6><h3><?= (int)$vehicleCounters['disponiveis'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><h6>Alugados</h6><h3><?= (int)$vehicleCounters['alugados'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><h6>Manutenção</h6><h3><?= (int)$vehicleCounters['manutencao'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><h6>Total clientes</h6><h3><?= $totalClients ?></h3></div></div></div>
  <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><h6>Contratos ativos</h6><h3><?= $activeContracts ?></h3></div></div></div>
  <div class="col-md-2"><div class="card card-kpi"><div class="card-body"><h6>Receitas mês</h6><h5>R$ <?= number_format($monthFinancial['receitas'],2,',','.') ?></h5></div></div></div>
  <div class="col-md-2"><div class="card card-kpi"><div class="card-body"><h6>Despesas mês</h6><h5>R$ <?= number_format($monthFinancial['despesas'],2,',','.') ?></h5></div></div></div>
  <div class="col-md-2"><div class="card card-kpi"><div class="card-body"><h6>Lucro mês</h6><h5>R$ <?= number_format($monthFinancial['lucro'],2,',','.') ?></h5></div></div></div>
</div>
<div class="row g-3">
  <div class="col-md-4"><div class="card"><div class="card-header">Últimas locações</div><ul class="list-group list-group-flush"><?php foreach ($latestRentals as $item): ?><li class="list-group-item"><?= esc($item['cliente_nome']) ?> - <?= esc($item['veiculo_nome']) ?> (<?= esc($item['status']) ?>)</li><?php endforeach; ?></ul></div></div>
  <div class="col-md-4"><div class="card"><div class="card-header">Manutenções pendentes</div><ul class="list-group list-group-flush"><?php foreach ($pendingMaintenances as $item): ?><li class="list-group-item"><?= esc($item['veiculo_nome']) ?> - <?= esc($item['tipo_manutencao']) ?></li><?php endforeach; ?></ul></div></div>
  <div class="col-md-4"><div class="card"><div class="card-header">Alertas de vencimento</div><ul class="list-group list-group-flush"><?php foreach ($dueAlerts as $item): ?><li class="list-group-item"><div><strong><?= esc($item['cliente_nome']) ?></strong> - <?= esc($item['veiculo_nome']) ?> (<?= esc($item['placa']) ?>)</div><small><?= esc(date('d/m/Y', strtotime((string)$item['data_prevista_termino']))) ?> · <?= esc($alertLabel((string)$item['alerta_status'])) ?> · WhatsApp: <?= esc((string)$item['whatsapp_delivery_status']) ?></small></li><?php endforeach; ?></ul></div></div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
