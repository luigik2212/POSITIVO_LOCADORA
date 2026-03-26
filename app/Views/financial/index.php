<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card card-kpi"><div class="card-body"><h6>Receitas</h6><h3 class="text-success">R$ <?= number_format($totals['receitas'],2,',','.') ?></h3></div></div></div>
  <div class="col-md-4"><div class="card card-kpi"><div class="card-body"><h6>Despesas</h6><h3 class="text-danger">R$ <?= number_format($totals['despesas'],2,',','.') ?></h3></div></div></div>
  <div class="col-md-4"><div class="card card-kpi"><div class="card-body"><h6>Lucro / Prejuízo</h6><h3 class="<?= $totals['lucro']>=0?'text-primary':'text-danger' ?>">R$ <?= number_format($totals['lucro'],2,',','.') ?></h3></div></div></div>
</div>

<div class="card mb-3">
  <div class="card-header">Próximos vencimentos</div>
  <div class="list-group list-group-flush">
    <?php if (!$upcomingDue): ?>
      <div class="list-group-item text-muted">Nenhum lançamento pendente para os próximos dias.</div>
    <?php else: foreach ($upcomingDue as $idx => $due): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center <?= $idx === 0 ? 'financial-due-soon' : '' ?>">
        <div>
          <strong><?= esc($due['descricao']) ?></strong>
          <div class="small text-muted"><?= esc(date('d/m/Y', strtotime((string)$due['data_movimentacao']))) ?> • <?= esc($due['categoria']) ?></div>
        </div>
        <span class="badge <?= $idx === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">R$ <?= number_format($due['valor'],2,',','.') ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="GET" class="d-flex gap-2">
    <input type="hidden" name="tab" value="<?= esc($tab) ?>">
    <input type="date" name="from" class="form-control" value="<?= esc($from??'') ?>">
    <input type="date" name="to" class="form-control" value="<?= esc($to??'') ?>">
    <button class="btn btn-outline-primary">Filtrar</button>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#financialModal" onclick="openFinancialModal()">Nova movimentação</button>
</div>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab === 'payable' ? 'active' : '' ?>" href="<?= url('/financial') ?>?tab=payable&from=<?= esc($from) ?>&to=<?= esc($to) ?>">Contas a pagar</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab === 'receivable' ? 'active' : '' ?>" href="<?= url('/financial') ?>?tab=receivable&from=<?= esc($from) ?>&to=<?= esc($to) ?>">Contas a receber</a></li>
</ul>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Descrição</th><th>Valor</th><th>Pagamento</th><th>Veículo</th><th>Cliente</th><th class="text-end">Ações</th></tr></thead>
  <tbody><?php foreach($entries as $e): ?><tr>
    <td><?= esc(date('d/m/Y', strtotime((string)$e['data_movimentacao']))) ?></td><td><?= esc($e['tipo']) ?></td><td><?= esc($e['categoria']) ?></td><td><?= esc($e['descricao']) ?><?= !empty($e['recorrente']) ? ' <span class="badge bg-info">Recorrente</span>' : '' ?></td>
    <td>R$ <?= number_format($e['valor'],2,',','.') ?></td>
    <td>
      <form method="POST" action="<?= url('/financial/payment-status') ?>" class="d-flex gap-1 align-items-center">
        <input type="hidden" name="_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="id" value="<?= $e['id'] ?>">
        <select class="form-select form-select-sm" name="pagamento_status" onchange="this.form.submit()">
          <option value="nao_pago" <?= ($e['pagamento_status'] ?? 'nao_pago') === 'nao_pago' ? 'selected' : '' ?>>Não pago</option>
          <option value="pago" <?= ($e['pagamento_status'] ?? '') === 'pago' ? 'selected' : '' ?>>Pago</option>
        </select>
      </form>
    </td>
    <td><?= esc($e['veiculo_nome']) ?></td><td><?= esc($e['cliente_nome']) ?></td>
    <td class="text-end">
      <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#financialModal" onclick='openFinancialModal(<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
      <form method="POST" action="<?= url('/financial/delete') ?>" class="d-inline" onsubmit="return confirm('Excluir movimentação?')"><input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-sm btn-danger">Excluir</button></form>
    </td>
  </tr><?php endforeach; ?></tbody>
</table>
</div>
<div class="modal fade" id="financialModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="<?= url('/financial/store') ?>" id="financialForm"><div class="modal-header"><h5>Movimentação financeira</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" id="f_id">
<div class="col-6"><label class="form-label">Tipo</label><select class="form-select" name="tipo" id="f_tipo"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
<div class="col-6"><label class="form-label">Categoria</label><input class="form-control" name="categoria" id="f_categoria" required></div>
<div class="col-12"><label class="form-label">Descrição</label><input class="form-control" name="descricao" id="f_descricao" required></div>
<div class="col-6"><label class="form-label">Valor</label><input type="number" step="0.01" class="form-control" name="valor" id="f_valor" required></div>
<div class="col-6"><label class="form-label">Data</label><input type="date" class="form-control" name="data_movimentacao" id="f_data_movimentacao" required></div>
<div class="col-6"><label class="form-label">Status pagamento</label><select class="form-select" name="pagamento_status" id="f_pagamento_status"><option value="nao_pago">Não pago</option><option value="pago">Pago</option></select></div>
<div class="col-6"><label class="form-label">Conta recorrente?</label><select class="form-select" name="recorrente" id="f_recorrente" onchange="toggleRecurring()"><option value="0">Não</option><option value="1">Sim</option></select></div>
<div class="col-12 d-none" id="recorrencia_wrap"><label class="form-label">Periodicidade</label><select class="form-select" name="recorrencia_periodo" id="f_recorrencia_periodo"><option value="mensal">Mensal</option><option value="semanal">Semanal</option></select></div>
<div class="col-6"><label class="form-label">ID veículo (opcional)</label><input class="form-control" name="vehicle_id" id="f_vehicle_id"></div>
<div class="col-6"><label class="form-label">ID cliente (opcional)</label><input class="form-control" name="client_id" id="f_client_id"></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
