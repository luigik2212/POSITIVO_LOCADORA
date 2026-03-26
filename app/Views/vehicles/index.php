<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
  <form class="row g-2" method="GET">
    <div class="col"><input class="form-control" name="search" placeholder="Buscar nome/placa" value="<?= esc($_GET['search'] ?? '') ?>"></div>
    <div class="col"><select name="status" class="form-select"><option value="">Status</option><?php foreach (['disponivel','alugado','manutencao','inativo'] as $s): ?><option value="<?= $s ?>" <?= (($_GET['status'] ?? '')===$s)?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <div class="col"><button class="btn btn-outline-primary">Filtrar</button></div>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="openVehicleModal()">Adicionar veículo</button>
</div>
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>ID</th><th>Nome</th><th>Placa</th><th>Status</th><th>KM</th><th>Diária</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($vehicles as $v): ?>
<tr>
  <td><?= $v['id'] ?></td><td><?= esc($v['nome']) ?></td><td><?= esc($v['placa']) ?></td><td><span class="badge bg-secondary"><?= esc($v['status']) ?></span></td><td><?= (int)$v['quilometragem_atual'] ?></td><td>R$ <?= number_format($v['valor_diaria'],2,',','.') ?></td>
  <td>
    <button class="btn btn-sm btn-warning" onclick='openVehicleModal(<?= json_encode($v, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
    <form method="POST" action="/vehicles/inactivate" class="d-inline" onsubmit="return confirm('Inativar veículo?')"><input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $v['id'] ?>"><button class="btn btn-sm btn-danger">Inativar</button></form>
  </td>
</tr>
<?php endforeach; ?></tbody></table></div>

<div class="modal fade" id="vehicleModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" id="vehicleForm" action="/vehicles/store"><div class="modal-header"><h5 class="modal-title">Veículo</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" id="vehicle_id">
<?php $fields=['nome','marca','modelo','ano','placa','renavam','cor','quilometragem_atual','categoria','valor_diaria','valor_semanal','valor_mensal']; foreach($fields as $f): ?><div class="col-md-4"><label class="form-label"><?= ucfirst(str_replace('_',' ',$f)) ?></label><input required class="form-control" name="<?= $f ?>" id="v_<?= $f ?>"></div><?php endforeach; ?>
<div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status" id="v_status"><?php foreach(['disponivel','alugado','manutencao','inativo'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes" id="v_observacoes"></textarea></div>
</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Fechar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
