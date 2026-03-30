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

function formatMoneyBr(value) {
  const amount = Number(value || 0);
  return `R$ ${amount.toFixed(2).replace('.', ',')}`;
}


function handlePaymentStatusChange(event, select) {
  const form = select?.form;
  if (!form) return;

  const nextStatus = select.value;
  const previousStatus = Array.from(select.options).find((option) => option.defaultSelected)?.value || 'nao_pago';
  const requiresKm = select.dataset.requiresKm === '1';

  if (nextStatus === 'pago' && requiresKm) {
    event.preventDefault();
    const modalEl = document.getElementById('weeklyMileageModal');
    const input = document.getElementById('weeklyMileageInput');
    const hint = document.getElementById('weeklyMileageHint');
    const vehicleText = document.getElementById('weeklyMileageVehicle');
    const entryIdField = document.getElementById('weeklyMileageEntryId');
    if (!modalEl || !input || !entryIdField) {
      form.submit();
      return;
    }

    const currentKm = Number(select.dataset.currentKm || 0);
    const vehicleLabel = select.dataset.vehicleLabel || '';
    const idField = form.querySelector('input[name="id"]');
    entryIdField.value = idField ? idField.value : '';
    input.min = String(Math.max(currentKm, 0));
    input.value = String(Math.max(currentKm, 0));
    if (hint) hint.textContent = `KM atual cadastrado: ${currentKm}`;
    if (vehicleText) vehicleText.textContent = vehicleLabel ? `Veículo: ${vehicleLabel}` : '';

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    modalEl.addEventListener('hidden.bs.modal', () => {
      select.value = previousStatus;
    }, { once: true });
    return;
  }

  form.submit();
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
  const finesSummaryMap = window.rentalFinesSummaryMap || {};
  const rentalId = Number(rental?.id || 0);
  const fineSummary = finesSummaryMap[rentalId] || finesSummaryMap[String(rentalId)] || null;
  const qtdMultas = fineSummary ? Number(fineSummary.qtd || 0) : Number(rental.total_multas_qtd || 0);
  const valorMultas = fineSummary ? Number(fineSummary.valor_total || 0) : Number(rental.total_multas_valor || 0);

  const map = {
    cliente: rental.cliente_nome,
    veiculo: `${rental.veiculo_nome} (${rental.placa})`,
    status: rental.status,
    tipo: rental.tipo_cobranca,
    tempo: rental.tempo_contrato,
    inicio: formatDateBr(rental.data_inicio),
    fim: formatDateBr(rental.data_prevista_termino),
    km_saida: rental.quilometragem_saida,
    km_retorno: rental.quilometragem_retorno || '-',
    valor: formatMoneyBr(rental.valor_total_previsto || 0),
    caucao: formatMoneyBr(rental.caucao || 0),
    fim_real: rental.data_real_termino ? formatDateBr(rental.data_real_termino) : '-',
    fin_total: formatMoneyBr(rental.financeiro_total_lancamentos || 0),
    fin_pago: formatMoneyBr(rental.financeiro_total_pago || 0),
    fin_pendente: formatMoneyBr(rental.financeiro_total_pendente || 0),
    multas_qtd: qtdMultas,
    multas_total: formatMoneyBr(valorMultas),
    obs: rental.observacoes || '-',
  };

  Object.entries(map).forEach(([key, value]) => {
    const el = document.getElementById('view_' + key);
    if (el) el.textContent = value;
  });

  if (typeof window.fetch === 'function') {
    fetch(withBase(`/fines/summary?rental_id=${rental.id}`))
      .then((response) => response.ok ? response.json() : null)
      .then((summary) => {
        if (!summary) return;
        const qtd = document.getElementById('view_multas_qtd');
        const total = document.getElementById('view_multas_total');
        if (qtd) qtd.textContent = String(Number(summary.qtd || 0));
        if (total) total.textContent = formatMoneyBr(summary.valor_total || 0);
      })
      .catch(() => {});
  }

  const actionsWrap = document.getElementById('view_actions_wrap');
  const cancelId = document.getElementById('view_cancel_id');
  const devolverBtn = document.getElementById('view_devolver_btn');
  const manageFinesLink = document.getElementById('view_manage_fines_link');
  if (cancelId) cancelId.value = rental.id;
  if (devolverBtn) {
    devolverBtn.onclick = () => fillFinalize(rental);
  }
  if (manageFinesLink) {
    manageFinesLink.href = withBase(`/fines?rental_id=${rental.id}`);
  }
  if (actionsWrap) {
    actionsWrap.classList.toggle('d-none', rental.status !== 'ativa');
  }
}

function openFineModal(fine = null) {
  const form = document.getElementById('fineForm');
  if (!form) return;
  const title = document.getElementById('fineModalTitle');
  form.action = fine ? withBase('/fines/update') : withBase('/fines/store');
  if (title) title.textContent = fine ? 'Editar multa' : 'Cadastro de multa';

  const id = document.getElementById('fine_id');
  if (id) id.value = fine?.id || '';

  const fields = ['rental_id', 'auto_infracao', 'local_infracao', 'valor', 'data_hora_multa', 'data_vencimento', 'observacoes'];
  fields.forEach((field) => {
    const el = document.getElementById('fine_' + field);
    if (!el) return;
    if (field === 'data_hora_multa') {
      const raw = fine?.[field] || '';
      el.value = raw ? String(raw).replace(' ', 'T').slice(0, 16) : '';
      return;
    }
    el.value = fine?.[field] ?? '';
  });

  const checkbox = document.getElementById('fine_gerar_despesa_financeiro');
  if (checkbox) checkbox.checked = Boolean(Number(fine?.gerar_despesa_financeiro || 0));

  if (!fine) {
    const preset = Number(window.finePresetRentalId || 0);
    const rentalSelect = document.getElementById('fine_rental_id');
    if (preset > 0 && rentalSelect) {
      rentalSelect.value = String(preset);
    }
  }
}

function parseFineFromElement(element) {
  if (!element || !element.dataset || !element.dataset.fine) return null;

  const raw = String(element.dataset.fine || '').trim();
  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch (error) {
    // fallback
  }

  try {

    const decoded = window.atob(raw);
    const normalized = decodeURIComponent(Array.from(decoded)
      .map((char) => `%${char.charCodeAt(0).toString(16).padStart(2, '0')}`)
      .join(''));
    return JSON.parse(normalized);

  } catch (error) {
    return null;
  }
}

function openFineModalFromElement(element) {
  openFineModal(parseFineFromElement(element));
}

function openFineView(fine) {
  const map = {
    rental: `#${fine.rental_id}`,
    financial: fine.financial_entry_id ? 'Gerada no financeiro' : 'Não gerada',
    cliente: fine.cliente_nome || '-',
    veiculo: `${fine.veiculo_nome || '-'} (${fine.placa || '-'})`,
    auto: fine.auto_infracao || '-',
    local: fine.local_infracao || '-',
    valor: formatMoneyBr(fine.valor || 0),
    vencimento: formatDateBr(fine.data_vencimento),
    obs: fine.observacoes || '-',
  };

  Object.entries(map).forEach(([key, value]) => {
    const el = document.getElementById('fine_view_' + key);
    if (el) el.textContent = value;
  });
}

function openFineViewFromElement(element) {
  const fine = parseFineFromElement(element);
  if (!fine) return;
  openFineView(fine);
}

function openFineAttachmentModal(fine) {
  const id = document.getElementById('fine_attachment_id');
  const context = document.getElementById('fine_attachment_context');
  if (id) id.value = fine.id || '';
  if (context) {
    context.textContent = `Multa #${fine.id} | Locação #${fine.rental_id} | Auto ${fine.auto_infracao || '-'}`;
  }
}

function openFineAttachmentModalFromElement(element) {
  const fine = parseFineFromElement(element);
  if (!fine) return;
  openFineAttachmentModal(fine);
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
  if (vehicleSearch && vehicleId) {
    const vehicles = Array.isArray(window.financialVehicles) ? window.financialVehicles : [];
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const buildVehicleLabel = (vehicle) => `${vehicle.nome || ''} (${vehicle.placa || ''})`.trim();

    const syncVehicleId = () => {
      const normalized = normalize(vehicleSearch.value);
      if (!normalized) {
        vehicleId.value = '';
        return;
      }

      const match = vehicles.find((vehicle) => {
        const byLabel = normalize(buildVehicleLabel(vehicle)) === normalized;
        const byName = normalize(vehicle.nome) === normalized;
        const byPlate = normalize(vehicle.placa) === normalized;
        return byLabel || byName || byPlate;
      });

      vehicleId.value = match ? String(match.id || '') : '';
    };

    vehicleSearch.addEventListener('input', syncVehicleId);
    vehicleSearch.addEventListener('change', syncVehicleId);

    const financialForm = document.getElementById('financialForm');
    if (financialForm) {
      financialForm.addEventListener('submit', (event) => {
        syncVehicleId();
        if (vehicleSearch.value.trim() && !vehicleId.value) {
          event.preventDefault();
          window.alert('Selecione um veículo válido na lista para salvar a despesa.');
          vehicleSearch.focus();
        }
      });
    }
  }


  const weeklyMileageForm = document.getElementById('weeklyMileageForm');
  if (weeklyMileageForm) {
    weeklyMileageForm.addEventListener('submit', (event) => {
      const input = document.getElementById('weeklyMileageInput');
      const minKm = Number(input?.min || 0);
      const valueKm = Number(input?.value || 0);
      if (!input || !input.value || valueKm < minKm) {
        event.preventDefault();
        window.alert('Informe um KM válido (igual ou maior ao KM atual).');
        input?.focus();
      }
    });
  }

  ['vehicleSelect', 'tipoCobranca', 'tempoContrato'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', updatePricePreview);
    if (el) el.addEventListener('input', updatePricePreview);
  });
  updatePricePreview();
  toggleRecurring();
});
