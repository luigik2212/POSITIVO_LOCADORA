<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Relatórios</h5>
  <button class="btn btn-outline-primary" onclick="window.print()">Imprimir</button>
</div>

<div class="card mb-4">
  <div class="card-header">Filtro geral de período (relatório de carros)</div>
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <input type="hidden" name="vehicle_id" value="<?= esc((string)$vehicleId) ?>">
      <input type="hidden" name="vehicle_search" value="<?= esc($vehicleSearch) ?>">
      <input type="hidden" name="period_type" value="<?= esc($periodType) ?>">
      <?php if ($month !== null): ?>
        <input type="hidden" name="month" value="<?= esc((string)$month) ?>">
      <?php endif; ?>
      <input type="hidden" name="year" value="<?= esc((string)$year) ?>">

      <div class="col-md-4">
        <label class="form-label">De</label>
        <input type="date" name="from" class="form-control" value="<?= esc($from) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Até</label>
        <input type="date" name="to" class="form-control" value="<?= esc($to) ?>">
      </div>
      <div class="col-md-4">
        <button class="btn btn-outline-primary w-100">Atualizar período</button>
      </div>
    </form>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">Relatório de carro</div>
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end mb-3">
      <input type="hidden" name="from" value="<?= esc($from) ?>">
      <input type="hidden" name="to" value="<?= esc($to) ?>">
      <input type="hidden" name="period_type" value="<?= esc($periodType) ?>">
      <?php if ($month !== null): ?>
        <input type="hidden" name="month" value="<?= esc((string)$month) ?>">
      <?php endif; ?>
      <input type="hidden" name="year" value="<?= esc((string)$year) ?>">

      <div class="col-md-4">
        <label class="form-label">Busca por nome ou placa</label>
        <input type="text" name="vehicle_search" class="form-control" placeholder="Ex.: Onix ou QWE4R56" value="<?= esc($vehicleSearch) ?>">
      </div>
      <div class="col-md-5">
        <label class="form-label">Carro específico</label>
        <select name="vehicle_id" class="form-select">
          <option value="">Selecione um carro</option>
          <?php foreach ($vehicles as $v): ?>
            <option value="<?= $v['id'] ?>" <?= ((string)$vehicleId === (string)$v['id']) ? 'selected' : '' ?>>
              <?= esc($v['nome']) ?> - <?= esc($v['placa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100">Gerar relatório do carro</button>
      </div>
    </form>

    <?php if (!$selectedVehicle): ?>
      <div class="alert alert-info mb-0">Selecione um veículo para visualizar o relatório detalhado de lucros, manutenção e contratos.</div>
    <?php else: ?>
      <div class="mb-3">
        <strong>Veículo:</strong> <?= esc($selectedVehicle['nome']) ?> (<?= esc($selectedVehicle['placa']) ?>)
        <span class="text-muted ms-2">Período de análise: <?= esc(date('d/m/Y', strtotime((string)$from))) ?> até <?= esc(date('d/m/Y', strtotime((string)$to))) ?></span>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><small>Receita do carro</small><h5>R$ <?= number_format($vehicleReport['receitas'], 2, ',', '.') ?></h5></div></div></div>
        <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><small>Gastos manutenção</small><h5>R$ <?= number_format($vehicleReport['gastos_manutencao'], 2, ',', '.') ?></h5></div></div></div>
        <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><small>Saldo do carro</small><h5>R$ <?= number_format($vehicleReport['saldo'], 2, ',', '.') ?></h5></div></div></div>
        <div class="col-md-3"><div class="card card-kpi"><div class="card-body"><small>Locações/contratos</small><h5><?= (int)$vehicleReport['qtd_locacoes'] ?></h5></div></div></div>
      </div>

      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>Total de contratos</th>
              <th>Ativos</th>
              <th>Finalizados</th>
              <th>Cancelados</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><?= (int)$vehicleReport['qtd_locacoes'] ?></td>
              <td><?= (int)$vehicleReport['qtd_ativas'] ?></td>
              <td><?= (int)$vehicleReport['qtd_finalizadas'] ?></td>
              <td><?= (int)$vehicleReport['qtd_canceladas'] ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">Relatório financeiro</div>
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end mb-3">
      <input type="hidden" name="from" value="<?= esc($from) ?>">
      <input type="hidden" name="to" value="<?= esc($to) ?>">
      <input type="hidden" name="vehicle_id" value="<?= esc((string)$vehicleId) ?>">
      <input type="hidden" name="vehicle_search" value="<?= esc($vehicleSearch) ?>">

      <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select name="period_type" class="form-select" onchange="this.form.submit()">
          <option value="month" <?= $periodType === 'month' ? 'selected' : '' ?>>Por mês</option>
          <option value="year" <?= $periodType === 'year' ? 'selected' : '' ?>>Por ano</option>
        </select>
      </div>

      <?php if ($periodType === 'month'): ?>
        <div class="col-md-3">
          <label class="form-label">Mês</label>
          <select name="month" class="form-select">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>
        </div>
      <?php endif; ?>

      <div class="col-md-3">
        <label class="form-label">Ano</label>
        <input type="number" name="year" class="form-control" min="2000" max="2100" value="<?= esc((string)$year) ?>">
      </div>

      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100">Atualizar financeiro</button>
      </div>
    </form>

    <div class="row g-2 mb-3">
      <div class="col-md-4"><div class="card"><div class="card-body"><small>Receitas</small><h5>R$ <?= number_format($financialSummary['receitas'], 2, ',', '.') ?></h5></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><small>Despesas</small><h5>R$ <?= number_format($financialSummary['despesas'], 2, ',', '.') ?></h5></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><small>Saldo/Lucro</small><h5>R$ <?= number_format($financialSummary['saldo'], 2, ',', '.') ?></h5></div></div></div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>Mês</th>
            <th>Receitas</th>
            <th>Despesas</th>
            <th>Saldo</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$financialEvolution): ?>
            <tr><td colspan="4" class="text-center text-muted">Nenhuma movimentação financeira encontrada para o ano selecionado.</td></tr>
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
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
