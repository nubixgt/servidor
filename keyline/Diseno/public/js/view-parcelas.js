const ViewParcelas = (() => {
  let allParcelas = [];
  let currentUser = null;

  async function render(container, opts = {}) {
    currentUser = App.currentUser;
    const c = window.KL.constants;
    container.innerHTML = `
      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Base consolidada de parcelas</h3><p>Busca, filtra, revisa y administra la información del proyecto.</p></div>
          <button class="btn btn-secondary" id="btnExportCsv">⬇️ Exportar CSV</button>
        </div>
        <div class="toolbar">
          <div class="left">
            <input id="searchInput" class="search-box" placeholder="Buscar por finca, municipio, departamento o responsable" value="${UI.escapeHtml(opts.presetSearch || '')}" />
            <select id="filterDepartamento"><option value="">Todos los departamentos</option>${UI.optionsHtml(c.DEPARTAMENTOS)}</select>
            <select id="filterEstado"><option value="">Todos los estados</option>${UI.optionsHtml(c.ESTADOS_PROCESO)}</select>
            <select id="filterValidacion"><option value="">Toda validación</option>${UI.optionsHtml(c.ESTADOS_VALIDACION)}</select>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Parcela</th><th>Ubicación</th><th>Área</th><th>Estado</th><th>Validación</th><th>Técnico</th><th>Fotos</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
        </div>
        <div class="empty-state" id="emptyState" hidden>No hay parcelas que coincidan con los filtros actuales.</div>
      </section>
    `;

    document.getElementById('searchInput').addEventListener('input', debounce(load, 300));
    document.getElementById('filterDepartamento').addEventListener('change', load);
    document.getElementById('filterEstado').addEventListener('change', load);
    document.getElementById('filterValidacion').addEventListener('change', load);
    document.getElementById('btnExportCsv').addEventListener('click', exportCsv);

    await load();
  }

  function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

  async function load() {
    const params = {
      q: document.getElementById('searchInput').value,
      departamento: document.getElementById('filterDepartamento').value,
      estado: document.getElementById('filterEstado').value,
      estadoValidacion: document.getElementById('filterValidacion').value,
    };
    const { parcelas } = await Api.listarParcelas(params);
    allParcelas = parcelas;
    renderTable(parcelas);
  }

  function renderTable(data) {
    const body = document.getElementById('tableBody');
    document.getElementById('emptyState').hidden = data.length > 0;
    body.innerHTML = data.map((p) => `
      <tr>
        <td><strong>${UI.escapeHtml(p.nombreParcela)}</strong><br><span class="hint">${UI.escapeHtml(p.codigo)} · ${UI.fmtFecha(p.fechaRegistro)}</span></td>
        <td>${UI.escapeHtml(p.departamento)} / ${UI.escapeHtml(p.municipio)}<br><span class="hint">${p.latitud !== '' ? `${p.latitud}, ${p.longitud}` : 'Sin GPS'}</span></td>
        <td>${UI.fmtHa(p.areaHa)}</td>
        <td>${UI.estadoTagHtml(p.estado)}</td>
        <td>${UI.validacionTagHtml(p.estadoValidacion)}</td>
        <td>${UI.escapeHtml(p.tecnicoNombre)}</td>
        <td>${p.fotos.length ? `📷 ${p.fotos.length}` : '—'}</td>
        <td class="row-actions">
          <button class="btn btn-secondary btn-sm" data-view="${p.id}">Ver</button>
          ${(currentUser.role === 'administrador' || currentUser.role === 'supervisor') ? `<button class="btn btn-warning btn-sm" data-review="${p.id}">Revisar</button>` : ''}
          ${currentUser.role === 'administrador' ? `<button class="btn btn-danger btn-sm" data-delete="${p.id}">Eliminar</button>` : ''}
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-view]').forEach((b) => b.addEventListener('click', () => openDetail(b.dataset.view)));
    body.querySelectorAll('[data-review]').forEach((b) => b.addEventListener('click', () => openReview(b.dataset.review)));
    body.querySelectorAll('[data-delete]').forEach((b) => b.addEventListener('click', () => deleteParcela(b.dataset.delete)));
  }

  async function openDetail(id) {
    const { parcela: p } = await Api.obtenerParcela(id);
    const fotosHtml = (p.fotos || []).map((f) => `
      <div class="photo-thumb"><img src="/uploads/parcelas/${f.miniatura || f.archivo}" alt="foto" /></div>
    `).join('') || '<p class="hint">Sin fotos registradas.</p>';

    UI.openModal(`
      <div class="modal-head"><h3>${UI.escapeHtml(p.nombreParcela)}</h3><button class="modal-close" id="mdX">✕</button></div>
      <div class="pc-tags" style="margin-bottom:14px;">${UI.estadoTagHtml(p.estado)} ${UI.validacionTagHtml(p.estadoValidacion)} <span class="badge">${UI.escapeHtml(p.codigo)}</span></div>
      <div class="form-grid">
        <div class="field"><label>Ubicación</label><div>${UI.escapeHtml(p.departamento)} / ${UI.escapeHtml(p.municipio)} ${p.comunidad ? '· ' + UI.escapeHtml(p.comunidad) : ''}</div></div>
        <div class="field"><label>GPS</label><div>${p.latitud !== '' ? `${p.latitud}, ${p.longitud}` : 'No registrado'}</div></div>
        <div class="field"><label>Área</label><div>${UI.fmtHa(p.areaHa)}</div></div>
        <div class="field"><label>Uso actual</label><div>${UI.escapeHtml(p.usoActual || 'N/D')}</div></div>
        <div class="field"><label>Suelo</label><div>${UI.escapeHtml(p.tipoSuelo || 'N/D')} · Prof. ${p.profundidadSuelo || 'N/D'} cm</div></div>
        <div class="field"><label>Talpetate / Encharca</label><div>${p.talpetate || 'N/D'} / ${p.encharca || 'N/D'}</div></div>
        <div class="field"><label>Agua</label><div>${UI.escapeHtml(p.agua || 'N/D')} · ${UI.escapeHtml(p.fuenteAgua || '')}</div></div>
        <div class="field"><label>Lluvia anual</label><div>${p.lluviaAnual !== '' ? p.lluviaAnual + ' mm' : 'N/D'}</div></div>
        <div class="field full"><label>Bioindicadores</label><div>${UI.escapeHtml(p.bioindicadores || 'Sin registro')}</div></div>
        <div class="field full"><label>Intervenciones</label><div>${UI.escapeHtml(p.intervenciones || 'Sin detalle')}</div></div>
        <div class="field full"><label>Observaciones</label><div>${UI.escapeHtml(p.observaciones || '—')}</div></div>
        <div class="field"><label>Responsable / técnico</label><div>${UI.escapeHtml(p.propietario || 'N/D')} · Cargado por ${UI.escapeHtml(p.tecnicoNombre)}</div></div>
        ${p.comentarioSupervisor ? `<div class="field full"><label>Comentario del supervisor</label><div>${UI.escapeHtml(p.comentarioSupervisor)}</div></div>` : ''}
      </div>
      <div class="divider"></div>
      <label style="font-size:12.5px;font-weight:700;color:var(--text-dim);text-transform:uppercase;">Fotografías</label>
      <div class="photo-grid" style="margin-top:8px;">${fotosHtml}</div>
    `, { wide: true });
    document.getElementById('mdX').onclick = UI.closeModal;
  }

  async function openReview(id) {
    const { parcela: p } = await Api.obtenerParcela(id);
    const c = window.KL.constants;
    UI.openModal(`
      <div class="modal-head"><h3>Revisar parcela</h3><button class="modal-close" id="mrX">✕</button></div>
      <p class="hint" style="margin-bottom:14px;">${UI.escapeHtml(p.nombreParcela)} · ${UI.escapeHtml(p.codigo)}</p>
      <div class="field">
        <label>Estado de validación</label>
        <select id="revEstado">${UI.optionsHtml(c.ESTADOS_VALIDACION, p.estadoValidacion)}</select>
      </div>
      <div class="field">
        <label>Comentario para el técnico</label>
        <textarea id="revComentario" placeholder="Observaciones, correcciones solicitadas...">${UI.escapeHtml(p.comentarioSupervisor || '')}</textarea>
      </div>
      <div class="modal-actions">
        <button class="btn btn-ghost" id="revCancel">Cancelar</button>
        <button class="btn btn-primary" id="revSave">Guardar revisión</button>
      </div>
    `);
    document.getElementById('mrX').onclick = UI.closeModal;
    document.getElementById('revCancel').onclick = UI.closeModal;
    document.getElementById('revSave').onclick = async () => {
      const estadoValidacion = document.getElementById('revEstado').value;
      const comentario = document.getElementById('revComentario').value;
      await Api.revisarParcela(id, estadoValidacion, comentario);
      UI.closeModal();
      UI.toast('Revisión guardada.', 'success');
      load();
    };
  }

  async function deleteParcela(id) {
    const ok = await UI.confirmDialog('Esta acción eliminará la parcela y sus fotos de forma permanente. ¿Continuar?', { danger: true, title: 'Eliminar parcela' });
    if (!ok) return;
    await Api.eliminarParcela(id);
    UI.toast('Parcela eliminada.', 'info');
    load();
  }

  function exportCsv() {
    if (!allParcelas.length) { UI.toast('No hay datos para exportar.', 'info'); return; }
    const cols = ['codigo', 'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'areaHa', 'estado', 'estadoValidacion', 'usoActual', 'tipoSuelo', 'pendiente', 'altitud', 'agua', 'fuenteAgua', 'riesgoErosion', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'observaciones', 'tecnicoNombre', 'fechaRegistro', 'latitud', 'longitud'];
    const rows = [cols.join(',')].concat(allParcelas.map((p) => cols.map((c) => csvEscape(p[c])).join(',')));
    const blob = new Blob(['﻿' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `parcelas_keyline_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }
  function csvEscape(v) {
    const s = String(v ?? '');
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  }

  return { render };
})();
