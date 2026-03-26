<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between mb-3">
<form method="GET" class="row g-2">
  <div class="col"><select name="status" class="form-select"><option value="">Status</option><?php foreach(['ativa','finalizada','cancelada'] as $s): ?><option value="<?= $s ?>" <?= (($filters['status']??'')===$s)?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <div class="col"><select name="billing_type" class="form-select"><option value="">Cobrança</option><?php foreach(['diaria','semanal','mensal'] as $t): ?><option value="<?= $t ?>" <?= (($filters['billing_type']??'')===$t)?'selected':'' ?>><?= ucfirst($t) ?></option><?php endforeach; ?></select></div>
  <div class="col"><input type="date" name="from" class="form-control" value="<?= esc($filters['from']??'') ?>"></div>
  <div class="col"><input type="date" name="to" class="form-control" value="<?= esc($filters['to']??'') ?>"></div>
  <div class="col"><button class="btn btn-outline-primary">Filtrar</button></div>
</form>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rentalModal">Nova locação</button>
</div>
<table class="table table-striped"><thead><tr><th>Cliente</th><th>Veículo</th><th>Tipo</th><th>Previsto</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($rentals as $r): ?><tr><td><?= esc($r['cliente_nome']) ?></td><td><?= esc($r['veiculo_nome']) ?> (<?= esc($r['placa']) ?>)</td><td><?= esc($r['tipo_cobranca']) ?></td><td>R$ <?= number_format($r['valor_total_previsto'],2,',','.') ?></td><td><?= esc($r['status']) ?></td><td>
<?php if($r['status']==='ativa'): ?>
<button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#finalizeModal" onclick='fillFinalize(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Devolver</button>
<form method="POST" action="/rentals/cancel" class="d-inline" onsubmit="return confirm('Cancelar locação?')"><input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-danger">Cancelar</button></form>
<?php endif; ?></td></tr><?php endforeach; ?></tbody></table>

<div class="modal fade" id="rentalModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><form method="POST" action="/rentals/store" enctype="multipart/form-data" id="rentalForm"><div class="modal-header"><h5>Nova locação</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>">
<div class="col-md-6"><label class="form-label">Cliente</label><select required name="client_id" class="form-select" id="clientSelect"><?php foreach($clients as $c): ?><option value="<?= $c['id'] ?>" data-cpf="<?= esc($c['cpf']) ?>"><?= esc($c['nome_completo']) ?> - <?= esc($c['cpf']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Veículo (apenas disponíveis)</label><select required name="vehicle_id" class="form-select" id="vehicleSelect"><?php foreach($vehicles as $v): ?><option value="<?= $v['id'] ?>" data-diaria="<?= $v['valor_diaria'] ?>" data-semanal="<?= $v['valor_semanal'] ?>" data-mensal="<?= $v['valor_mensal'] ?>"><?= esc($v['nome']) ?> - <?= esc($v['placa']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Tipo cobrança</label><select name="tipo_cobranca" id="tipoCobranca" class="form-select"><option value="diaria">Diária</option><option value="semanal">Semanal</option><option value="mensal">Mensal</option></select></div>
<div class="col-md-3"><label class="form-label">Tempo contrato</label><input required type="number" min="1" name="tempo_contrato" id="tempoContrato" class="form-control" value="1"></div>
<div class="col-md-3"><label class="form-label">Valor cobrança</label><input readonly name="valor_cobranca_preview" id="valorCobrancaPreview" class="form-control"></div>
<div class="col-md-3 d-none" id="diaSemanaWrap"><label class="form-label">Dia semanal</label><select class="form-select" name="dia_semana_vencimento"><option>segunda</option><option>terca</option><option>quarta</option><option>quinta</option><option>sexta</option><option>sabado</option><option>domingo</option></select></div>
<div class="col-md-3"><label class="form-label">Início</label><input required type="date" name="data_inicio" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Término previsto</label><input required type="date" name="data_prevista_termino" class="form-control"></div>
<div class="col-md-3"><label class="form-label">KM saída</label><input required type="number" name="quilometragem_saida" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Caução</label><input type="number" step="0.01" name="caucao" class="form-control" value="0"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes"></textarea></div>
<?php foreach(['lataria','pneus','vidros','combustivel','limpeza','interior','acessorios','avarias'] as $it): ?><div class="col-md-3"><label class="form-label">Entrega - <?= ucfirst($it) ?></label><input class="form-control" name="checklist_entrega_<?= $it ?>"></div><?php endforeach; ?>
<div class="col-12"><label class="form-label">Entrega - observações</label><textarea class="form-control" name="checklist_entrega_observacoes"></textarea></div>
<div class="col-12"><label class="form-label">Anexos entrega (foto/vídeo)</label><input class="form-control" type="file" name="anexos_entrega[]" multiple accept="image/*,video/*"></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-primary">Salvar locação</button></div></form></div></div></div>

<div class="modal fade" id="finalizeModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" action="/rentals/finalize" enctype="multipart/form-data"><div class="modal-header"><h5>Finalizar locação</h5></div><div class="modal-body row g-2">
<input type="hidden" name="_token" value="<?= csrfToken() ?>"><input type="hidden" name="id" id="finalize_id">
<div class="col-md-6"><label class="form-label">Data real término</label><input type="date" class="form-control" name="data_real_termino" required></div>
<div class="col-md-6"><label class="form-label">KM retorno</label><input type="number" class="form-control" name="quilometragem_retorno" required></div>
<div class="col-md-6"><label class="form-label">Valor final</label><input type="number" step="0.01" class="form-control" name="valor_total_final"></div>
<div class="col-md-6"><label class="form-label">Retornar para manutenção?</label><select name="retornar_para_manutencao" class="form-select"><option value="0">Não</option><option value="1">Sim</option></select></div>
<?php foreach(['lataria','pneus','vidros','combustivel','limpeza','interior','acessorios','avarias'] as $it): ?><div class="col-md-3"><label class="form-label">Devolução - <?= ucfirst($it) ?></label><input class="form-control" name="checklist_devolucao_<?= $it ?>"></div><?php endforeach; ?>
<div class="col-12"><label class="form-label">Devolução - observações</label><textarea class="form-control" name="checklist_devolucao_observacoes"></textarea></div>
<div class="col-12"><label class="form-label">Anexos devolução</label><input type="file" class="form-control" name="anexos_devolucao[]" multiple accept="image/*,video/*"></div>
</div><div class="modal-footer"><button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Fechar</button><button class="btn btn-success">Finalizar</button></div></form></div></div></div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
