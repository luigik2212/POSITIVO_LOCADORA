<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório - Lgk Locadora</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding: 24px; }
    .pdf-header { border-bottom: 2px solid #d71920; padding-bottom: 12px; margin-bottom: 18px; }
    .pdf-logo { max-width: 145px; height: auto; }
    .pdf-company { font-weight: 700; font-size: 1.1rem; color: #111827; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0">Pré-visualização para PDF</h5>
    <button class="btn btn-primary" onclick="window.print()">Baixar/Imprimir PDF</button>
  </div>


  <div class="pdf-header d-flex justify-content-between align-items-center">
    <div>
      <img src="<?= url('/assets/img-lgk-logo.svg') ?>" alt="Logo Lgk Locadora" class="pdf-logo">
    </div>
    <div class="text-end">
      <div class="pdf-company">Lgk Locadora</div>
      <small class="text-muted">Relatório gerado em <?= date('d/m/Y H:i') ?></small>
    </div>
  </div>

  <h4 class="mb-3">Relatório <?= $reportType === 'financial' ? 'Financeiro' : 'de Carro' ?></h4>
  <p><strong>Período:</strong> <?= esc(date('d/m/Y', strtotime((string)$from))) ?> até <?= esc(date('d/m/Y', strtotime((string)$to))) ?></p>

  <?php if ($reportType === 'vehicle'): ?>
    <?php if (!$selectedVehicle): ?>
      <div class="alert alert-warning">Nenhum veículo selecionado para gerar PDF.</div>
    <?php else: ?>
      <p><strong>Veículo:</strong> <?= esc($selectedVehicle['nome']) ?> (<?= esc($selectedVehicle['placa']) ?>)</p>
      <table class="table table-bordered">
        <tbody>
          <tr><th>Receita do carro</th><td>R$ <?= number_format($vehicleReport['receitas'], 2, ',', '.') ?></td></tr>
          <tr><th>Gastos manutenção</th><td>R$ <?= number_format($vehicleReport['gastos_manutencao'], 2, ',', '.') ?></td></tr>
          <tr><th>Saldo</th><td>R$ <?= number_format($vehicleReport['saldo'], 2, ',', '.') ?></td></tr>
          <tr><th>Quantidade de contratos</th><td><?= (int)$vehicleReport['qtd_locacoes'] ?></td></tr>
        </tbody>
      </table>
    <?php endif; ?>
  <?php else: ?>
    <p><strong>Filtro financeiro:</strong> <?= $periodType === 'month' ? 'Mês ' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '/' . esc((string)$year) : 'Ano ' . esc((string)$year) ?></p>
    <table class="table table-bordered mb-3">
      <tbody>
        <tr><th>Receitas</th><td>R$ <?= number_format($financialSummary['receitas'], 2, ',', '.') ?></td></tr>
        <tr><th>Despesas</th><td>R$ <?= number_format($financialSummary['despesas'], 2, ',', '.') ?></td></tr>
        <tr><th>Saldo/Lucro</th><td>R$ <?= number_format($financialSummary['saldo'], 2, ',', '.') ?></td></tr>
      </tbody>
    </table>

    <table class="table table-striped table-bordered">
      <thead><tr><th>Mês</th><th>Receitas</th><th>Despesas</th><th>Saldo</th></tr></thead>
      <tbody>
      <?php if (!$financialEvolution): ?>
        <tr><td colspan="4" class="text-center text-muted">Sem dados.</td></tr>
      <?php else: foreach ($financialEvolution as $item): ?>
        <tr>
          <td><?= str_pad((string)$item['mes'], 2, '0', STR_PAD_LEFT) ?>/<?= esc((string)$year) ?></td>
          <td>R$ <?= number_format($item['receitas'], 2, ',', '.') ?></td>
          <td>R$ <?= number_format($item['despesas'], 2, ',', '.') ?></td>
          <td>R$ <?= number_format($item['saldo'], 2, ',', '.') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <script>window.print();</script>
</body>
</html>
