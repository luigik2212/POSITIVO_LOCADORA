function openVehicleModal(vehicle = null) {
  const form = document.getElementById('vehicleForm');
  if (!form) return;
  form.action = vehicle ? '/vehicles/update' : '/vehicles/store';
  document.getElementById('vehicle_id').value = vehicle?.id || '';
  ['nome','marca','modelo','ano','placa','renavam','cor','quilometragem_atual','categoria','valor_diaria','valor_semanal','valor_mensal','status','observacoes'].forEach(k => {
    const el = document.getElementById('v_' + k);
    if (el) el.value = vehicle?.[k] ?? (k === 'status' ? 'disponivel' : '');
  });
}

function openClientModal(client = null) {
  const form = document.getElementById('clientForm');
  if (!form) return;
  form.action = client ? '/clients/update' : '/clients/store';
  document.getElementById('client_id').value = client?.id || '';
  ['nome_completo','cpf','rg','cnh','validade_cnh','telefone','email','endereco_completo','observacoes'].forEach(k => {
    const el = document.getElementById('c_' + k);
    if (el) el.value = client?.[k] ?? '';
  });
}

function fillFinalize(rental) {
  const field = document.getElementById('finalize_id');
  if (field) field.value = rental.id;
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
  ['vehicleSelect', 'tipoCobranca', 'tempoContrato'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', updatePricePreview);
    if (el) el.addEventListener('input', updatePricePreview);
  });
  updatePricePreview();
});
