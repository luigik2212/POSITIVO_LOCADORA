<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
  <form class="d-flex gap-2" method="GET"><input class="form-control" name="search" placeholder="Buscar nome/CPF/telefone" value="<?= esc($_GET['search'] ?? '') ?>"><button class="btn btn-outline-primary">Buscar</button></form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientModal" onclick="openClientModal()">Adicionar cliente</button>
</div>
<table class="table table-striped"><thead><tr><th>Nome</th><th>CPF</th><th>Telefone</th><th>E-mail</th><th>Ações</th></tr></thead><tbody>
<?php foreach($clients as $c): ?><tr><td><?= esc($c['nome_completo']) ?></td><td><?= esc($c['cpf']) ?></td><td><?= esc($c['telefone']) ?></td><td><?= esc($c['email']) ?></td><td>
<button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#clientModal" onclick='openClientModal(<?= json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Editar</button>
<a class="btn btn-sm btn-info" href="<?= url('/clients') ?>?client_id=<?= $c['id'] ?>">Histórico</a>
</td></tr><?php endforeach; ?></tbody></table>

<?php if ($selectedClient): ?>
<div class="card"><div class="card-header">Histórico de locações: <?= esc($selectedClient['nome_completo']) ?></div><div class="table-responsive"><table class="table"><thead><tr><th>Veículo</th><th>Início</th><th>Término</th><th>Status</th></tr></thead><tbody><?php foreach($history as $h): ?><tr><td><?= esc($h['veiculo_nome']) ?></td><td><?= esc($h['data_inicio']) ?></td><td><?= esc($h['data_prevista_termino']) ?></td><td><?= esc($h['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>

<div class="modal fade" id="clientModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" action="<?= url('/clients/store') ?>" id="clientForm"><div class="modal-header"><h5>Cliente</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" id="client_id">
<?php $fields=['nome_completo','cpf','rg','cnh','validade_cnh','telefone','email']; foreach($fields as $f): ?><div class="col-md-6"><label class="form-label"><?= ucfirst(str_replace('_',' ',$f)) ?></label><input class="form-control" type="<?= $f === 'validade_cnh' ? 'date' : 'text' ?>" name="<?= $f ?>" id="c_<?= $f ?>" <?= in_array($f,['nome_completo','cpf'])?'required':'' ?>></div><?php endforeach; ?>
<div class="col-12"><label class="form-label">Endereço completo</label><textarea class="form-control" name="endereco_completo" id="c_endereco_completo"></textarea></div>
<div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes" id="c_observacoes"></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
