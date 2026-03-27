<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Relatório financeiro</h5>
  <div class="d-flex gap-2">
    <a href="<?= url('/financial') ?>" class="btn btn-outline-secondary">Voltar</a>
    <button class="btn btn-outline-primary" onclick="window.print()">Imprimir</button>
  </div>
</div>

<form method="GET" class="row g-2 mb-3">
  <div class="col-md-4"><input type="date" name="from" class="form-control" value="<?= esc($from) ?>"></div>
  <div class="col-md-4"><input type="date" name="to" class="form-control" value="<?= esc($to) ?>"></div>
  <div class="col-md-4"><button class="btn btn-outline-primary w-100">Atualizar relatório</button></div>
</form>
<small class="text-muted d-block mb-3">Período selecionado: <?= esc(date('d/m/Y', strtotime((string)$from))) ?> até <?= esc(date('d/m/Y', strtotime((string)$to))) ?></small>

<div class="row g-2 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Receitas</small><h5>R$ <?= number_format($summary['receitas'],2,',','.') ?></h5></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Despesas</small><h5>R$ <?= number_format($summary['despesas'],2,',','.') ?></h5></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Saldo</small><h5>R$ <?= number_format($summary['saldo'],2,',','.') ?></h5></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Recorrências ativas</small><h5><?= (int)$summary['recorrencias_ativas'] ?></h5></div></div></div>
</div>
<div class="row g-2 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Pendente a receber</small><h6>R$ <?= number_format($summary['pendente_receber'],2,',','.') ?></h6></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Pendente a pagar</small><h6>R$ <?= number_format($summary['pendente_pagar'],2,',','.') ?></h6></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Total pago</small><h6>R$ <?= number_format($summary['pagas'],2,',','.') ?></h6></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Total não pago</small><h6>R$ <?= number_format($summary['nao_pagas'],2,',','.') ?></h6></div></div></div>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Descrição</th><th>Valor</th><th>Pagamento</th><th>Recorrência</th><th>Vencimento/Ref</th></tr></thead>
    <tbody>
    <?php if (!$entries): ?>
      <tr><td colspan="8" class="text-center text-muted">Sem lançamentos no período.</td></tr>
    <?php else: foreach($entries as $e): ?>
      <tr>
        <td><?= esc(date('d/m/Y', strtotime((string)$e['data_movimentacao']))) ?></td>
        <td><?= esc($e['tipo']) ?></td>
        <td><?= esc($e['categoria']) ?></td>
        <td><?= esc($e['descricao']) ?></td>
        <td>R$ <?= number_format((float)$e['valor'],2,',','.') ?></td>
        <td><?= esc($e['pagamento_status'] ?? 'nao_pago') ?></td>
        <td><?= !empty($e['recorrente']) ? esc((string)$e['recorrencia_periodo']) : '-' ?></td>
        <td><?= esc(date('d/m/Y', strtotime((string)($e['referencia_data'] ?: $e['data_movimentacao'])))) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
