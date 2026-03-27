<?php require __DIR__ . '/../partials/header.php'; ?>

<section class="dashboard-section mb-4">
  <div class="dashboard-section-title mb-3">
    <h5 class="mb-1">Visão geral da operação</h5>
    <p class="text-muted mb-0">Indicadores principais da frota e contratos ativos.</p>
  </div>

  <div class="row g-3">
    <div class="col-sm-6 col-xl-3">
      <div class="card card-kpi card-kpi-neutral h-100">
        <div class="card-body">
          <div class="kpi-label">Total veículos</div>
          <div class="kpi-value"><?= (int)$vehicleCounters['total'] ?></div>
          <div class="kpi-subtitle">Frota cadastrada</div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card card-kpi card-kpi-success h-100">
        <div class="card-body">
          <div class="kpi-label">Disponíveis</div>
          <div class="kpi-value"><?= (int)$vehicleCounters['disponiveis'] ?></div>
          <div class="kpi-subtitle">Prontos para locação</div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card card-kpi card-kpi-primary h-100">
        <div class="card-body">
          <div class="kpi-label">Locações ativas</div>
          <div class="kpi-value"><?= $activeContracts ?></div>
          <div class="kpi-subtitle">Contratos em andamento</div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card card-kpi card-kpi-warning h-100">
        <div class="card-body">
          <div class="kpi-label">Em manutenção</div>
          <div class="kpi-value"><?= (int)$vehicleCounters['manutencao'] ?></div>
          <div class="kpi-subtitle">Aguardando liberação</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section mb-4">
  <div class="dashboard-section-title mb-3">
    <h5 class="mb-1">Resumo financeiro do mês</h5>
    <p class="text-muted mb-0">Acompanhamento de receitas, despesas e resultado mensal.</p>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card card-kpi card-kpi-finance-income h-100">
        <div class="card-body">
          <div class="kpi-label">Faturamento</div>
          <div class="kpi-currency">R$ <?= number_format($monthFinancial['receitas'],2,',','.') ?></div>
          <div class="kpi-subtitle">Receitas no período</div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-kpi card-kpi-finance-expense h-100">
        <div class="card-body">
          <div class="kpi-label">Despesas</div>
          <div class="kpi-currency">R$ <?= number_format($monthFinancial['despesas'],2,',','.') ?></div>
          <div class="kpi-subtitle">Custos registrados</div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-kpi card-kpi-finance-profit h-100">
        <div class="card-body">
          <div class="kpi-label">Lucro</div>
          <div class="kpi-currency">R$ <?= number_format($monthFinancial['lucro'],2,',','.') ?></div>
          <div class="kpi-subtitle">Resultado do mês</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section mb-4">
  <div class="row g-3">
    <div class="col-md-6 col-xl-4">
      <div class="card card-kpi h-100">
        <div class="card-body">
          <div class="kpi-label">Total clientes</div>
          <div class="kpi-value"><?= $totalClients ?></div>
          <div class="kpi-subtitle">Base ativa da locadora</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-4">
      <div class="card card-kpi h-100">
        <div class="card-body">
          <div class="kpi-label">Veículos alugados</div>
          <div class="kpi-value"><?= (int)$vehicleCounters['alugados'] ?></div>
          <div class="kpi-subtitle">Unidades em uso</div>
        </div>
      </div>
    </div>

    <div class="col-md-12 col-xl-4">
      <div class="card card-kpi h-100">
        <div class="card-body">
          <div class="kpi-label">Taxa de ocupação da frota</div>
          <?php $occupancy = $vehicleCounters['total'] > 0 ? (($vehicleCounters['alugados'] / $vehicleCounters['total']) * 100) : 0; ?>
          <div class="kpi-value"><?= number_format($occupancy, 1, ',', '.') ?>%</div>
          <div class="progress mt-2 dashboard-progress" role="progressbar" aria-label="Taxa de ocupação" aria-valuenow="<?= (int)$occupancy ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= min(100, max(0, $occupancy)) ?>%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="dashboard-section">
  <div class="dashboard-section-title mb-3">
    <h5 class="mb-1">Acompanhamento diário</h5>
    <p class="text-muted mb-0">Movimentações recentes para apoio na tomada de decisão.</p>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card dashboard-list-card h-100">
        <div class="card-header">Últimas locações</div>
        <ul class="list-group list-group-flush">
          <?php if (!empty($latestRentals)): ?>
            <?php foreach ($latestRentals as $item): ?>
              <li class="list-group-item">
                <div class="fw-semibold"><?= esc($item['cliente_nome']) ?></div>
                <div class="small text-muted"><?= esc($item['veiculo_nome']) ?> · <?= esc($item['status']) ?></div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">Nenhuma locação recente.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card dashboard-list-card h-100">
        <div class="card-header">Manutenções pendentes</div>
        <ul class="list-group list-group-flush">
          <?php if (!empty($pendingMaintenances)): ?>
            <?php foreach ($pendingMaintenances as $item): ?>
              <li class="list-group-item">
                <div class="fw-semibold"><?= esc($item['veiculo_nome']) ?></div>
                <div class="small text-muted"><?= esc($item['tipo_manutencao']) ?></div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">Nenhuma manutenção pendente.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card dashboard-list-card h-100">
        <div class="card-header">Próximos vencimentos</div>
        <ul class="list-group list-group-flush">
          <?php if (!empty($upcomingRentals)): ?>
            <?php foreach ($upcomingRentals as $item): ?>
              <li class="list-group-item">
                <div class="fw-semibold"><?= esc($item['cliente_nome']) ?></div>
                <div class="small text-muted">Término previsto: <?= esc($item['data_prevista_termino']) ?></div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">Sem vencimentos próximos.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
