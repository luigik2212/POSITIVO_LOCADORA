<?php require __DIR__ . '/../partials/header.php'; ?>
<?php
$statusClasses = [
  'pendente' => 'bg-warning text-dark',
  'paga' => 'bg-success',
  'vencida' => 'bg-danger',
  'cancelada' => 'bg-secondary',
];
?>
<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card card-kpi"><div class="card-body"><h6>Quantidade de multas</h6><h3 class="text-warning"><?= (int)$totals['qtd'] ?></h3></div></div></div>
  <div class="col-md-6"><div class="card card-kpi"><div class="card-body"><h6>Valor total de multas</h6><h3 class="text-danger">R$ <?= number_format((float)$totals['valor'],2,',','.') ?></h3></div></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="GET" class="row g-2 flex-grow-1 me-2">
    <div class="col-md-2">
      <select name="rental_id" class="form-select">
        <option value="">Locação</option>
        <?php foreach ($rentals as $r): ?>
          <option value="<?= (int)$r['id'] ?>" <?= (string)($filters['rental_id'] ?? '') === (string)$r['id'] ? 'selected' : '' ?>>#<?= (int)$r['id'] ?> - <?= esc($r['cliente_nome'] ?? '') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="client_id" class="form-select"><option value="">Cliente</option><?php foreach ($clients as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (string)($filters['client_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= esc($c['nome_completo']) ?></option><?php endforeach; ?></select>
    </div>
    <div class="col-md-2"><input name="placa" class="form-control" placeholder="Placa" value="<?= esc($filters['placa'] ?? '') ?>"></div>
    <div class="col-md-2">
      <select name="status" class="form-select">
        <option value="">Status</option>
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= esc($value) ?>" <?= (string)($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-outline-primary w-100">Filtrar</button>
      <a class="btn btn-outline-secondary" href="<?= url('/fines') ?>">Limpar</a>
    </div>
  </form>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fineModal" onclick="openFineModal(null)">Nova multa</button>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead><tr><th>Locação</th><th>Cliente</th><th>Veículo</th><th>Placa</th><th>Auto</th><th>Local</th><th>Valor</th><th>Data multa</th><th>Vencimento</th><th>Status</th><th>Financeiro</th><th>Comprovante</th><th class="text-end">Ações</th></tr></thead>
    <tbody>
      <?php foreach ($fines as $fine): ?>
        <tr>
          <td>#<?= (int)$fine['rental_id'] ?></td>
          <td><?= esc($fine['cliente_nome']) ?></td>
          <td><?= esc($fine['veiculo_nome']) ?></td>
          <td><?= esc($fine['placa']) ?></td>
          <td><?= esc($fine['auto_infracao']) ?></td>
          <td><?= esc($fine['local_infracao']) ?></td>
          <td>R$ <?= number_format((float)$fine['valor'],2,',','.') ?></td>
          <td><?= esc(date('d/m/Y H:i', strtotime((string)$fine['data_hora_multa']))) ?></td>
          <td><?= esc(date('d/m/Y', strtotime((string)$fine['data_vencimento']))) ?></td>
          <?php
            $statusValue = (string)($fine['status_exibicao'] ?? $fine['status'] ?? 'pendente');
            $statusLabel = $statusOptions[$statusValue] ?? ucfirst($statusValue);
            $statusClass = $statusClasses[$statusValue] ?? 'bg-secondary';
          ?>
          <td><span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span></td>
          <td><?= !empty($fine['financial_entry_id']) ? '<span class="badge bg-success">Gerada</span>' : '<span class="badge bg-secondary">Não gerada</span>' ?></td>
          <td>
            <?php if (!empty($fine['comprovante_path'])): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= url('/fines/attachment/view?id=' . (int)$fine['id']) ?>">Ver</a>
              <a class="btn btn-sm btn-outline-secondary" href="<?= url('/fines/attachment/download?id=' . (int)$fine['id']) ?>">Baixar</a>
            <?php else: ?>
              <small class="text-muted">Sem comprovante</small>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php $finePayload = json_encode($fine, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#fineViewModal" data-fine='<?= $finePayload ?>' onclick="openFineViewFromElement(this)">Ver</button>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#fineModal" data-fine='<?= $finePayload ?>' onclick="openFineModalFromElement(this)">Editar</button>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fineAttachmentModal" data-fine='<?= $finePayload ?>' onclick="openFineAttachmentModalFromElement(this)">Comprovante</button>
            <form method="POST" action="<?= url('/fines/delete') ?>" class="d-inline" onsubmit="return confirm('Excluir multa?')">
              <input type="hidden" name="_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="id" value="<?= (int)$fine['id'] ?>">
              <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="fineModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="<?= url('/fines/store') ?>" id="fineForm"><div class="modal-header"><h5 id="fineModalTitle">Cadastro de multa</h5></div><div class="modal-body row g-2">
  <input type="hidden" name="_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="id" id="fine_id">
  <div class="col-12"><label class="form-label">Locação vinculada</label><select required class="form-select" name="rental_id" id="fine_rental_id"><?php foreach ($rentals as $r): ?><option value="<?= (int)$r['id'] ?>">#<?= (int)$r['id'] ?> - <?= esc($r['cliente_nome'] ?? '') ?> | <?= esc($r['veiculo_nome'] ?? '') ?> (<?= esc($r['placa'] ?? '') ?>)</option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Auto da infração</label><input required class="form-control" name="auto_infracao" id="fine_auto_infracao"></div>
  <div class="col-12"><label class="form-label">Local</label><input required class="form-control" name="local_infracao" id="fine_local_infracao"></div>
  <div class="col-md-6"><label class="form-label">Valor</label><input required type="number" min="0.01" step="0.01" class="form-control" name="valor" id="fine_valor"></div>
  <div class="col-md-6"><label class="form-label">Data e hora da multa</label><input required type="datetime-local" class="form-control" name="data_hora_multa" id="fine_data_hora_multa"></div>
  <div class="col-md-6"><label class="form-label">Data de vencimento</label><input required type="date" class="form-control" name="data_vencimento" id="fine_data_vencimento"></div>
  <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status" id="fine_status"><?php foreach ($statusOptions as $value => $label): ?><option value="<?= esc($value) ?>"><?= esc($label) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" value="1" name="gerar_despesa_financeiro" id="fine_gerar_despesa_financeiro"><label class="form-check-label" for="fine_gerar_despesa_financeiro">Gerar despesa no financeiro</label></div></div>
  <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes" id="fine_observacoes"></textarea></div>
</div><div class="modal-footer"><button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Fechar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>

<div class="modal fade" id="fineViewModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>Detalhes da multa</h5></div><div class="modal-body row g-2">
  <div class="col-6"><strong>Locação:</strong> <span id="fine_view_rental"></span></div>
  <div class="col-6"><strong>Status:</strong> <span id="fine_view_status"></span></div>
  <div class="col-6"><strong>Status financeiro:</strong> <span id="fine_view_financial"></span></div>
  <div class="col-12"><strong>Cliente:</strong> <span id="fine_view_cliente"></span></div>
  <div class="col-12"><strong>Veículo:</strong> <span id="fine_view_veiculo"></span></div>
  <div class="col-12"><strong>Auto:</strong> <span id="fine_view_auto"></span></div>
  <div class="col-12"><strong>Local:</strong> <span id="fine_view_local"></span></div>
  <div class="col-6"><strong>Valor:</strong> <span id="fine_view_valor"></span></div>
  <div class="col-6"><strong>Vencimento:</strong> <span id="fine_view_vencimento"></span></div>
  <div class="col-12"><strong>Observações:</strong> <span id="fine_view_obs"></span></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div></div></div></div>

<div class="modal fade" id="fineAttachmentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="<?= url('/fines/attachment/upload') ?>" enctype="multipart/form-data"><div class="modal-header"><h5>Anexar comprovante</h5></div><div class="modal-body">
  <input type="hidden" name="_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="id" id="fine_attachment_id">
  <p class="small text-muted mb-2" id="fine_attachment_context"></p>
  <input type="file" class="form-control" name="comprovante" required>
</div><div class="modal-footer"><button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Fechar</button><button class="btn btn-primary">Salvar comprovante</button></div></form></div></div></div>

<script>
  window.finePresetRentalId = <?= json_encode((int)($_GET['rental_id'] ?? 0)) ?>;
  window.fineStatusLabels = <?= json_encode($statusOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
