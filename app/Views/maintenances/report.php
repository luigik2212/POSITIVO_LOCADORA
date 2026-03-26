<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Relatório de manutenções</h5>
  <div class="d-flex gap-2">
    <a href="<?= url('/maintenances') ?>" class="btn btn-outline-secondary">Voltar</a>
    <button class="btn btn-outline-primary" onclick="window.print()">Imprimir</button>
  </div>
</div>

<form method="GET" class="row g-2 mb-3">
  <div class="col-md-4">
    <select name="vehicle_id" class="form-select">
      <option value="">Todos veículos</option>
      <?php foreach($vehicles as $v): ?>
        <option value="<?= $v['id'] ?>" <?= ((string)$vehicleId === (string)$v['id']) ? 'selected' : '' ?>><?= esc($v['nome']) ?> - <?= esc($v['placa']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?= esc($from) ?>"></div>
  <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?= esc($to) ?>"></div>
  <div class="col-md-2"><button class="btn btn-outline-primary w-100">Atualizar</button></div>
</form>

<div class="card mb-3"><div class="card-body d-flex justify-content-between"><span>Período: <strong><?= esc(date('d/m/Y', strtotime((string)$from))) ?> a <?= esc(date('d/m/Y', strtotime((string)$to))) ?></strong></span><span>Total gasto: <strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></span></div></div>

<div class="table-responsive">
  <table class="table table-striped">
    <thead><tr><th>Veículo</th><th>Tipo</th><th>Descrição</th><th>Data</th><th>KM</th><th>Valor</th><th>Oficina/Fornecedor</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!$maintenances): ?>
      <tr><td colspan="8" class="text-center text-muted">Nenhuma manutenção encontrada no período.</td></tr>
    <?php else: foreach($maintenances as $m): ?>
      <tr>
        <td><?= esc($m['veiculo_nome']) ?> (<?= esc($m['placa']) ?>)</td>
        <td><?= esc($m['tipo_manutencao']) ?></td>
        <td><?= esc($m['descricao']) ?></td>
        <td><?= esc(date('d/m/Y', strtotime((string)$m['data_manutencao']))) ?></td>
        <td><?= (int)$m['quilometragem_manutencao'] ?></td>
        <td>R$ <?= number_format((float)$m['valor_gasto'],2,',','.') ?></td>
        <td><?= esc($m['oficina_fornecedor']) ?></td>
        <td><?= esc($m['status']) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
