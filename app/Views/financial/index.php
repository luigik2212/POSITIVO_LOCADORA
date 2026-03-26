<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card card-kpi"><div class="card-body"><h6>Receitas</h6><h3 class="text-success">R$ <?= number_format($totals['receitas'],2,',','.') ?></h3></div></div></div>
  <div class="col-md-4"><div class="card card-kpi"><div class="card-body"><h6>Despesas</h6><h3 class="text-danger">R$ <?= number_format($totals['despesas'],2,',','.') ?></h3></div></div></div>
  <div class="col-md-4"><div class="card card-kpi"><div class="card-body"><h6>Lucro / Prejuízo</h6><h3 class="<?= $totals['lucro']>=0?'text-primary':'text-danger' ?>">R$ <?= number_format($totals['lucro'],2,',','.') ?></h3></div></div></div>
</div>
<div class="d-flex justify-content-between mb-3">
  <form method="GET" class="d-flex gap-2"><input type="date" name="from" class="form-control" value="<?= esc($from??'') ?>"><input type="date" name="to" class="form-control" value="<?= esc($to??'') ?>"><button class="btn btn-outline-primary">Filtrar</button></form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#financialModal">Nova movimentação</button>
</div>
<table class="table table-striped"><thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Descrição</th><th>Valor</th><th>Veículo</th><th>Cliente</th></tr></thead><tbody><?php foreach($entries as $e): ?><tr><td><?= esc($e['data_movimentacao']) ?></td><td><?= esc($e['tipo']) ?></td><td><?= esc($e['categoria']) ?></td><td><?= esc($e['descricao']) ?></td><td>R$ <?= number_format($e['valor'],2,',','.') ?></td><td><?= esc($e['veiculo_nome']) ?></td><td><?= esc($e['cliente_nome']) ?></td></tr><?php endforeach; ?></tbody></table>
<div class="modal fade" id="financialModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="/financial/store"><div class="modal-header"><h5>Movimentação financeira</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>">
<div class="col-6"><label class="form-label">Tipo</label><select class="form-select" name="tipo"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
<div class="col-6"><label class="form-label">Categoria</label><input class="form-control" name="categoria" required></div>
<div class="col-12"><label class="form-label">Descrição</label><input class="form-control" name="descricao" required></div>
<div class="col-6"><label class="form-label">Valor</label><input type="number" step="0.01" class="form-control" name="valor" required></div>
<div class="col-6"><label class="form-label">Data</label><input type="date" class="form-control" name="data_movimentacao" required></div>
<div class="col-6"><label class="form-label">ID veículo (opcional)</label><input class="form-control" name="vehicle_id"></div>
<div class="col-6"><label class="form-label">ID cliente (opcional)</label><input class="form-control" name="client_id"></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
