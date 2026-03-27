const BASE_PATH = (document.body?.dataset?.basePath || '').replace(/\/$/, '');

function withBase(path) {
  const normalized = path.startsWith('/') ? path : '/' + path;
  return (BASE_PATH ? BASE_PATH : '') + normalized;
}

function formatDateBr(value) {
  if (!value) return '-';
  const dt = new Date(value + 'T00:00:00');
  if (Number.isNaN(dt.getTime())) return value;
  return dt.toLocaleDateString('pt-BR');
}

function openVehicleModal(vehicle = null) {
  const form = document.getElementById('vehicleForm');
  if (!form) return;
  form.action = vehicle ? withBase('/vehicles/update') : withBase('/vehicles/store');
  document.getElementById('vehicle_id').value = vehicle?.id || '';
  ['nome','marca','modelo','ano','placa','renavam','cor','quilometragem_atual','categoria','valor_diaria','valor_semanal','valor_mensal','status','observacoes'].forEach(k => {
    const el = document.getElementById('v_' + k);
    if (el) el.value = vehicle?.[k] ?? (k === 'status' ? 'disponivel' : '');
  });
}

function openClientModal(client = null) {
  const form = document.getElementById('clientForm');
  if (!form) return;
  form.action = client ? withBase('/clients/update') : withBase('/clients/store');
  document.getElementById('client_id').value = client?.id || '';
  ['nome_completo','cpf','rg','cnh','validade_cnh','telefone','email','endereco_completo','observacoes'].forEach(k => {
    const el = document.getElementById('c_' + k);
    if (el) el.value = client?.[k] ?? '';
  });

  const wrap = document.getElementById('clientDocumentsWrap');
  const list = document.getElementById('clientDocumentsList');
  if (wrap && list) {
    list.innerHTML = '';
    const docs = client?.documents || [];
    if (docs.length) {
      wrap.classList.remove('d-none');
      docs.forEach((doc) => {
        const item = document.createElement('a');
        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        item.href = withBase(`/clients/document-download?client_id=${client.id}&document_id=${doc.id}`);
        item.textContent = doc.nome_original;
        const badge = document.createElement('small');
        badge.className = 'text-muted';
        badge.textContent = (doc.tamanho_bytes || 0) + ' bytes';
        item.appendChild(badge);
        list.appendChild(item);
      });
    } else {
      wrap.classList.add('d-none');
    }
  }
}

function openFinancialModal(entry = null, currentTab = 'payable') {
  const form = document.getElementById('financialForm');
  if (!form) return;
  form.action = entry ? withBase('/financial/update') : withBase('/financial/store');
  const fields = ['id','tipo','categoria','descricao','valor','data_movimentacao','client_id','pagamento_status','recorrente','recorrencia_periodo'];

  fields.forEach((k) => {
    const el = document.getElementById('f_' + k);
    if (!el) return;
    if (k === 'recorrente') {
      el.value = entry?.[k] ? '1' : '0';
      return;
    }
    el.value = entry?.[k] ?? (k === 'pagamento_status' ? 'nao_pago' : '');
  });

  const tab = currentTab === 'receivable' ? 'receivable' : 'payable';
  const fixedTipo = tab === 'receivable' ? 'receita' : 'despesa';

  const tabInput = document.getElementById('f_tab');
  if (tabInput) tabInput.value = tab;

  const tipoField = document.getElementById('f_tipo');
  if (tipoField) {
    tipoField.value = fixedTipo;
    tipoField.disabled = true;
  }

  if (!entry) {
    const status = document.getElementById('f_pagamento_status');
    if (status) status.value = 'nao_pago';
  }

  const vehicleIdField = document.getElementById('f_vehicle_id');
  const vehicleSearchField = document.getElementById('f_vehicle_search');
  if (vehicleIdField) vehicleIdField.value = entry?.vehicle_id ?? '';
  if (vehicleSearchField) {
    const vehicleLabel = [entry?.veiculo_nome, entry?.veiculo_placa ? `(${entry.veiculo_placa})` : ''].filter(Boolean).join(' ');
    vehicleSearchField.value = vehicleLabel;
  }

  toggleRecurring();
}

function toggleRecurring() {
  const recurring = document.getElementById('f_recorrente');
  const wrap = document.getElementById('recorrencia_wrap');
  if (!recurring || !wrap) return;
  wrap.classList.toggle('d-none', recurring.value !== '1');
}

function fillFinalize(rental) {
  const field = document.getElementById('finalize_id');
  if (field) field.value = rental.id;
}

function openRentalView(rental) {
  const map = {
    cliente: rental.cliente_nome,
    veiculo: `${rental.veiculo_nome} (${rental.placa})`,
    status: rental.status,
    tipo: rental.tipo_cobranca,
    tempo: rental.tempo_contrato,
    inicio: formatDateBr(rental.data_inicio),
    fim: formatDateBr(rental.data_prevista_termino),
    km_saida: rental.quilometragem_saida,
    valor: `R$ ${Number(rental.valor_total_previsto || 0).toFixed(2)}`,
    caucao: `R$ ${Number(rental.caucao || 0).toFixed(2)}`,
    obs: rental.observacoes || '-',
  };

  Object.entries(map).forEach(([key, value]) => {
    const el = document.getElementById('view_' + key);
    if (el) el.textContent = value;
  });

  const actionsWrap = document.getElementById('view_actions_wrap');
  const cancelId = document.getElementById('view_cancel_id');
  const devolverBtn = document.getElementById('view_devolver_btn');
  if (cancelId) cancelId.value = rental.id;
  if (devolverBtn) {
    devolverBtn.onclick = () => fillFinalize(rental);
  }
  if (actionsWrap) {
    actionsWrap.classList.toggle('d-none', rental.status !== 'ativa');
  }
}

function updatePricePreview() {
  const vehicle = document.getElementById('vehicleSelect');
  const billing = document.getElementById('tipoCobranca');
  const preview = document.getElementById('valorCobrancaPreview');
  const tempo = document.getElementById('tempoContrato');
  const dayWrap = document.getElementById('diaSemanaWrap');
  if (!vehicle || !billing || !preview || !tempo) return;
  const option = vehicle.options[vehicle.selectedIndex];
  const key = billing.value;
  const value = Number(option.dataset[key] || 0);
  preview.value = 'R$ ' + value.toFixed(2) + ' | Total: R$ ' + (value * Number(tempo.value || 1)).toFixed(2);
  if (dayWrap) dayWrap.classList.toggle('d-none', key !== 'semanal');
}

document.addEventListener('DOMContentLoaded', () => {
  const vehicleSearch = document.getElementById('f_vehicle_search');
  const vehicleId = document.getElementById('f_vehicle_id');
  const vehicleOptions = document.getElementById('f_vehicle_options');
  if (vehicleSearch && vehicleId && vehicleOptions) {
    const syncVehicleId = () => {
      const normalized = vehicleSearch.value.trim().toLowerCase();
      const match = Array.from(vehicleOptions.options).find((option) => option.value.trim().toLowerCase() === normalized);
      vehicleId.value = match ? (match.dataset.id || '') : '';
    };
    vehicleSearch.addEventListener('input', syncVehicleId);
    vehicleSearch.addEventListener('change', syncVehicleId);
  }

  ['vehicleSelect', 'tipoCobranca', 'tempoContrato'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', updatePricePreview);
    if (el) el.addEventListener('input', updatePricePreview);
  });
  updatePricePreview();
  toggleRecurring();
});
