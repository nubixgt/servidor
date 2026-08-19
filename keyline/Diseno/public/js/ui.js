const UI = (() => {
  function toast(message, type = 'info') {
    const root = document.getElementById('toastRoot');
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = message;
    root.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3800);
  }

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function openModal(innerHtml, { wide = false } = {}) {
    closeModal();
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.id = 'activeModal';
    overlay.innerHTML = `<div class="modal-box glass ${wide ? 'wide' : ''}">${innerHtml}</div>`;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.getElementById('modalRoot').appendChild(overlay);
    return overlay;
  }
  function closeModal() {
    const existing = document.getElementById('activeModal');
    if (existing) existing.remove();
  }

  function confirmDialog(message, { title = 'Confirmar', danger = false } = {}) {
    return new Promise((resolve) => {
      const overlay = openModal(`
        <div class="modal-head"><h3>${escapeHtml(title)}</h3><button class="modal-close" id="mcX">✕</button></div>
        <p class="hint" style="font-size:14px;color:var(--text);">${escapeHtml(message)}</p>
        <div class="modal-actions">
          <button class="btn btn-ghost" id="mcCancel">Cancelar</button>
          <button class="btn ${danger ? 'btn-danger' : 'btn-primary'}" id="mcOk">Confirmar</button>
        </div>
      `);
      overlay.querySelector('#mcX').onclick = () => { closeModal(); resolve(false); };
      overlay.querySelector('#mcCancel').onclick = () => { closeModal(); resolve(false); };
      overlay.querySelector('#mcOk').onclick = () => { closeModal(); resolve(true); };
    });
  }

  function estadoTagHtml(estado) {
    const map = { Implementado: 'tag-implementado', Diseño: 'tag-diseno', Levantamiento: 'tag-levantamiento', Pendiente: 'tag-pendiente' };
    return `<span class="tag ${map[estado] || 'tag-levantamiento'}">${escapeHtml(estado || '—')}</span>`;
  }
  function validacionTagHtml(estado) {
    const map = { 'Validado': 'tag-validado', 'Pendiente de revisión': 'tag-revision', 'Requiere corrección': 'tag-correccion' };
    return `<span class="tag ${map[estado] || 'tag-revision'}">${escapeHtml(estado || 'Pendiente de revisión')}</span>`;
  }

  function fmtHa(v) {
    const n = Number(v) || 0;
    return `${n.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ha`;
  }
  function fmtFecha(iso) {
    if (!iso) return '—';
    try {
      const d = new Date(iso);
      return d.toLocaleDateString('es-GT', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) { return iso; }
  }
  function fmtFechaHora(iso) {
    if (!iso) return '—';
    try {
      const d = new Date(iso);
      return d.toLocaleString('es-GT', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch (e) { return iso; }
  }

  function initials(name) {
    if (!name) return '--';
    const parts = String(name).trim().split(/\s+/);
    return ((parts[0]?.[0] || '') + (parts[1]?.[0] || '')).toUpperCase();
  }

  function optionsHtml(list, selected) {
    return list.map((v) => `<option value="${escapeHtml(v)}" ${v === selected ? 'selected' : ''}>${escapeHtml(v)}</option>`).join('');
  }

  return { toast, escapeHtml, openModal, closeModal, confirmDialog, estadoTagHtml, validacionTagHtml, fmtHa, fmtFecha, fmtFechaHora, initials, optionsHtml };
})();
