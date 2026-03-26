<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
<form method="GET" class="d-flex gap-2"><select name="vehicle_id" class="form-select"><option value="">Todos veículos</option><?php foreach($vehicles as $v): ?><option value="<?= $v['id'] ?>" <?= (($_GET['vehicle_id']??'')==$v['id'])?'selected':'' ?>><?= esc($v['nome']) ?> - <?= esc($v['placa']) ?></option><?php endforeach; ?></select><button class="btn btn-outline-primary">Filtrar</button></form>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintenanceModal">Nova manutenção</button>
</div>
<table class="table table-striped"><thead><tr><th>Veículo</th><th>Tipo</th><th>Data</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach($maintenances as $m): ?><tr><td><?= esc($m['veiculo_nome']) ?></td><td><?= esc($m['tipo_manutencao']) ?></td><td><?= esc($m['data_manutencao']) ?></td><td>R$ <?= number_format($m['valor_gasto'],2,',','.') ?></td><td><?= esc($m['status']) ?></td><td><?php if($m['status']==='pendente'): ?><form method="POST" action="/maintenances/update-status" class="d-inline"><input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="status" value="concluida"><button class="btn btn-sm btn-success">Concluir</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
<div class="card"><div class="card-header">Total gasto por veículo</div><ul class="list-group list-group-flush"><?php foreach($totals as $t): ?><li class="list-group-item"><?= esc($t['nome']) ?> (<?= esc($t['placa']) ?>): R$ <?= number_format($t['total_gasto'],2,',','.') ?></li><?php endforeach; ?></ul></div>

<div class="modal fade" id="maintenanceModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="/maintenances/store"><div class="modal-header"><h5>Nova manutenção</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>">
<div class="col-12"><label class="form-label">Veículo</label><select class="form-select" name="vehicle_id"><?php foreach($vehicles as $v): ?><option value="<?= $v['id'] ?>"><?= esc($v['nome']) ?> - <?= esc($v['placa']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Tipo</label><input class="form-control" name="tipo_manutencao" required></div>
<div class="col-12"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao"></textarea></div>
<div class="col-6"><label class="form-label">Data</label><input type="date" class="form-control" name="data_manutencao" required></div>
<div class="col-6"><label class="form-label">KM</label><input type="number" class="form-control" name="quilometragem_manutencao" required></div>
<div class="col-6"><label class="form-label">Valor</label><input type="number" step="0.01" class="form-control" name="valor_gasto" required></div>
<div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="pendente">Pendente</option><option value="concluida">Concluída</option></select></div>
<div class="col-12"><label class="form-label">Oficina/fornecedor</label><input class="form-control" name="oficina_fornecedor"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes"></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
