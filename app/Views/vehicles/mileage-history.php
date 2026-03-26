<?php require __DIR__ . '/../partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Histórico de quilometragem</h5>
  <a href="<?= url('/vehicles') ?>" class="btn btn-outline-secondary">Voltar para veículos</a>
</div>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead><tr><th>Veículo</th><th>KM anterior</th><th>KM novo</th><th>Data da atualização</th><th>Origem</th></tr></thead>
    <tbody>
      <?php foreach ($history as $item): ?>
        <tr>
          <td><?= esc($item['veiculo_nome']) ?> (<?= esc($item['placa']) ?>)</td>
          <td><?= (int)$item['km_anterior'] ?></td>
          <td><?= (int)$item['km_novo'] ?></td>
          <td><?= esc(date('d/m/Y H:i', strtotime((string)$item['data_atualizacao']))) ?></td>
          <td><span class="badge bg-light text-dark text-uppercase"><?= esc($item['origem_atualizacao']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
