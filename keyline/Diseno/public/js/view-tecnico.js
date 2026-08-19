/* Vista Técnico: formulario simple tipo asistente (wizard) para registrar/editar parcelas
   y listado de "Mis parcelas" con estado de revisión. */
const ViewTecnico = (() => {
  const STEPS = [
    { key: 'ubicacion', label: '1. Ubicación', icon: '📍' },
    { key: 'suelo', label: '2. Suelo y agua', icon: '🌱' },
    { key: 'intervencion', label: '3. Intervención', icon: '🛠️' },
    { key: 'fotos', label: '4. Fotos', icon: '📷' },
  ];

  let stepIndex = 0;
  let pendingPhotos = []; // File objects antes de guardar
  let editingId = null;
  let coordsCaptured = null;

  function todayISO() {
    const now = new Date();
    const tz = now.getTimezoneOffset();
    return new Date(now.getTime() - tz * 60000).toISOString().slice(0, 10);
  }

  async function render(container, opts = {}) {
    editingId = opts.editingId || null;
    stepIndex = 0;
    pendingPhotos = [];
    coordsCaptured = null;
    const c = window.KL.constants;
    let parcela = null;
    if (editingId) {
      const r = await Api.obtenerParcela(editingId);
      parcela = r.parcela;
    }

    container.innerHTML = `
      <div class="panel glass wizard">
        <div class="panel-head">
          <div>
            <h3>${editingId ? 'Editar parcela' : 'Registrar nueva parcela'}</h3>
            <p>Completa los datos técnicos de la parcela keyline. Los campos con * son obligatorios.</p>
          </div>
          ${editingId ? `<span class="badge">Código: ${UI.escapeHtml(parcela.codigo)}</span>` : ''}
        </div>
        <div class="wizard-steps" id="wizardSteps"></div>
        <form id="parcelaForm">${renderAllSteps(c)}</form>
      </div>
    `;

    fillSelectData(c, parcela);
    if (parcela) fillFormFromParcela(parcela);
    else document.getElementById('fechaRegistro').value = todayISO();

    bindEvents(container, parcela);
    renderSteps();
    showStep(0);
    if (parcela) renderExistingPhotos(parcela);
  }

  function renderAllSteps(c) {
    return `
      <div class="wizard-step-panel" data-step="ubicacion">
        <div class="form-grid">
          <div class="field full">
            <label>Nombre de la parcela / finca / terreno *</label>
            <input id="nombreParcela" required placeholder="Ej. Finca El Pinar" />
          </div>
          <div class="field">
            <label>Departamento *</label>
            <select id="departamento" required><option value="">Seleccione...</option>${UI.optionsHtml(c.DEPARTAMENTOS)}</select>
          </div>
          <div class="field">
            <label>Municipio *</label>
            <input id="municipio" required placeholder="Ej. Cobán" />
          </div>
          <div class="field">
            <label>Aldea / comunidad</label>
            <input id="comunidad" placeholder="Ej. Chisecito" />
          </div>
          <div class="field">
            <label>Fecha de registro</label>
            <input id="fechaRegistro" type="date" />
          </div>
          <div class="field">
            <label>Productor / responsable</label>
            <input id="propietario" placeholder="Nombre del responsable" />
          </div>
          <div class="field">
            <label>Teléfono / contacto</label>
            <input id="telefono" placeholder="Opcional" />
          </div>
          <div class="field">
            <label>Tenencia de la tierra</label>
            <select id="tenenciaTierra"><option value="">Sin especificar</option>${UI.optionsHtml(c.TENENCIA_TIERRA)}</select>
          </div>
          <div class="field">
            <label>Familias beneficiadas</label>
            <input id="numFamiliasBeneficiadas" type="number" min="0" step="1" placeholder="Ej. 4" />
          </div>
          <div class="field full">
            <div class="subgroup" style="border-style:solid;">
              <div class="subgroup-title"><span class="dot"></span> Ubicación GPS</div>
              <div class="geo-box">
                <button type="button" class="btn btn-secondary btn-sm" id="btnGeo">📍 Capturar ubicación actual</button>
                <span class="geo-status" id="geoStatus">Aún no capturada. Puedes ingresarla manualmente si lo prefieres.</span>
              </div>
              <div class="form-grid" style="margin-top:12px;">
                <div class="field"><label>Latitud</label><input id="latitud" type="number" step="any" placeholder="15.4700" /></div>
                <div class="field"><label>Longitud</label><input id="longitud" type="number" step="any" placeholder="-90.3700" /></div>
                <div class="field"><label>Altitud (msnm)</label><input id="altitud" type="number" min="0" step="1" placeholder="650" /></div>
                <div class="field"><label>Precisión GPS (m)</label><input id="gpsPrecision" type="number" min="0" step="1" placeholder="Automático" readonly /></div>
              </div>
            </div>
          </div>
          <div class="field">
            <label>Área (hectáreas) *</label>
            <input id="areaHa" type="number" min="0" step="0.01" required placeholder="12.50" />
          </div>
          <div class="field">
            <label>Estado del proceso *</label>
            <select id="estado" required>${UI.optionsHtml(c.ESTADOS_PROCESO)}</select>
          </div>
        </div>
      </div>

      <div class="wizard-step-panel" data-step="suelo" hidden>
        <div class="form-grid">
          <div class="field">
            <label>Uso actual del suelo</label>
            <select id="usoActual">${UI.optionsHtml(c.USOS_ACTUALES)}</select>
          </div>
          <div class="field">
            <label>Cultivo principal</label>
            <input id="cultivoPrincipal" placeholder="Maíz, café, pastos..." />
          </div>
          <div class="field">
            <label>Tipo de suelo</label>
            <input id="tipoSuelo" placeholder="Franco, arcilloso, limoso..." />
          </div>
          <div class="field">
            <label>Pendiente estimada (%)</label>
            <input id="pendiente" type="number" min="0" step="0.1" placeholder="8.5" />
          </div>
          <div class="field">
            <label>Disponibilidad de agua</label>
            <select id="agua">${UI.optionsHtml(c.NIVELES_AGUA)}</select>
          </div>
          <div class="field">
            <label>Fuente de agua</label>
            <select id="fuenteAgua"><option value="">Sin especificar</option>${UI.optionsHtml(c.FUENTE_AGUA)}</select>
          </div>
          <div class="field">
            <label>Riesgo de erosión</label>
            <select id="riesgoErosion">${UI.optionsHtml(c.RIESGO_EROSION)}</select>
          </div>
          <div class="field">
            <label>Sistema de riego</label>
            <input id="sistemaRiego" placeholder="Goteo, aspersión, ninguno..." />
          </div>

          <div class="subgroup">
            <div class="subgroup-title"><span class="dot"></span> Diagnóstico físico del suelo</div>
            <div class="form-grid">
              <div class="field"><label>Profundidad de suelo (cm)</label><input id="profundidadSuelo" type="number" min="0" step="1" placeholder="45" /></div>
              <div class="field"><label>Presencia de talpetate</label><select id="talpetate">${UI.optionsHtml(c.SI_NO_VACIO.map(v => v || 'Sin evaluar'))}</select></div>
              <div class="field"><label>¿Se encharca el agua en la parcela?</label><select id="encharca">${UI.optionsHtml(c.SI_NO_VACIO.map(v => v || 'Sin evaluar'))}</select></div>
              <div class="field full"><label>Bioindicadores de suelo <span class="hint-text">(Mafer Md)</span></label><input id="bioindicadores" placeholder="Lombrices, hormigas, hongos, hojarasca..." /></div>
            </div>
          </div>

          <div class="subgroup">
            <div class="subgroup-title"><span class="dot"></span> Lluvia (opcional)</div>
            <div class="form-grid">
              <div class="field"><label>Lluvia acumulada anual (mm)</label><input id="lluviaAnual" type="number" min="0" step="1" placeholder="1800" /></div>
              <div class="field"><label>Fuente / año</label><input id="lluviaFuente" placeholder="INSIVUMEH 2025, estación local..." /></div>
            </div>
          </div>
        </div>
      </div>

      <div class="wizard-step-panel" data-step="intervencion" hidden>
        <div class="form-grid">
          <div class="field full">
            <label>Intervenciones previstas / ejecutadas</label>
            <input id="intervenciones" placeholder="Canales keyline, reservorios, reforestación, zanjas de infiltración..." />
          </div>
          <div class="field full">
            <label>Especies usadas en reforestación / cobertura</label>
            <input id="especiesReforestacion" placeholder="Ej. madrecacao, gravilea, pasto de corte..." />
          </div>
          <div class="field">
            <label>Fecha próxima visita de seguimiento</label>
            <input id="fechaProximaVisita" type="date" />
          </div>
          <div class="field" style="display:flex; align-items:flex-end;">
            <label style="display:flex; align-items:center; gap:8px; font-weight:600; color:var(--text); text-transform:none; font-size:14px;">
              <input id="consentimientoProductor" type="checkbox" /> El productor autoriza el uso de esta información
            </label>
          </div>
          <div class="field full">
            <label>Observaciones</label>
            <textarea id="observaciones" placeholder="Notas técnicas, restricciones, acuerdos con el productor, próximos pasos..."></textarea>
          </div>
        </div>
      </div>

      <div class="wizard-step-panel" data-step="fotos" hidden>
        <label style="font-size:12.5px;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.04em;">Fotografías de la parcela</label>
        <p class="hint" style="margin:6px 0 12px;">Sube fotos del terreno, obras keyline, suelo o cualquier evidencia relevante. Puedes tomar la foto directamente desde tu celular.</p>
        <label class="photo-drop" id="photoDrop">
          <input type="file" id="photoInput" accept="image/*" multiple capture="environment" />
          <div>📷 Toca para tomar o seleccionar fotos</div>
          <div class="hint">JPG o PNG, máx. 12MB cada una</div>
        </label>
        <div class="photo-grid" id="existingPhotoGrid"></div>
        <div class="photo-grid" id="newPhotoGrid"></div>
      </div>

      <div class="wizard-actions">
        <button type="button" class="btn btn-ghost" id="btnPrev">← Anterior</button>
        <div style="display:flex; gap:10px;">
          <button type="button" class="btn btn-secondary" id="btnCancel">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnNext">Siguiente →</button>
          <button type="submit" class="btn btn-primary" id="btnSave" hidden>💾 Guardar parcela</button>
        </div>
      </div>
    `;
  }

  function fillSelectData() {}

  function fillFormFromParcela(p) {
    const ids = ['nombreParcela', 'departamento', 'municipio', 'comunidad', 'fechaRegistro', 'propietario', 'telefono',
      'tenenciaTierra', 'numFamiliasBeneficiadas', 'latitud', 'longitud', 'altitud', 'gpsPrecision', 'areaHa', 'estado',
      'usoActual', 'cultivoPrincipal', 'tipoSuelo', 'pendiente', 'agua', 'fuenteAgua', 'riesgoErosion', 'sistemaRiego',
      'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones',
      'especiesReforestacion', 'fechaProximaVisita', 'observaciones'];
    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (el && p[id] !== undefined && p[id] !== null) el.value = p[id];
    });
    document.getElementById('consentimientoProductor').checked = !!p.consentimientoProductor;
    if (p.latitud !== '' && p.longitud !== '') {
      coordsCaptured = { lat: p.latitud, lng: p.longitud };
      document.getElementById('geoStatus').textContent = `Ubicación guardada: ${p.latitud}, ${p.longitud}`;
    }
  }

  function renderSteps() {
    const el = document.getElementById('wizardSteps');
    el.innerHTML = STEPS.map((s, i) => `
      <div class="wizard-step ${i === stepIndex ? 'active' : ''} ${i < stepIndex ? 'done' : ''}" data-idx="${i}">${s.icon} ${s.label}</div>
    `).join('');
    el.querySelectorAll('.wizard-step').forEach((node) => {
      node.addEventListener('click', () => { showStep(Number(node.dataset.idx)); });
    });
  }

  function showStep(idx) {
    stepIndex = Math.max(0, Math.min(STEPS.length - 1, idx));
    document.querySelectorAll('.wizard-step-panel').forEach((p) => { p.hidden = p.dataset.step !== STEPS[stepIndex].key; });
    document.querySelectorAll('.wizard-step').forEach((n, i) => {
      n.classList.toggle('active', i === stepIndex);
      n.classList.toggle('done', i < stepIndex);
    });
    document.getElementById('btnPrev').style.visibility = stepIndex === 0 ? 'hidden' : 'visible';
    const isLast = stepIndex === STEPS.length - 1;
    document.getElementById('btnNext').hidden = isLast;
    document.getElementById('btnSave').hidden = !isLast;
  }

  function validateCurrentStep() {
    const panel = document.querySelector(`.wizard-step-panel[data-step="${STEPS[stepIndex].key}"]`);
    const invalid = panel.querySelector(':invalid');
    if (invalid) { invalid.reportValidity(); return false; }
    return true;
  }

  function renderExistingPhotos(parcela) {
    const grid = document.getElementById('existingPhotoGrid');
    if (!parcela.fotos || !parcela.fotos.length) { grid.innerHTML = ''; return; }
    grid.innerHTML = parcela.fotos.map((f) => `
      <div class="photo-thumb" data-foto-id="${f.id}">
        <img src="/uploads/parcelas/${f.miniatura || f.archivo}" alt="foto parcela" />
        <button type="button" class="rm" data-remove-existing="${f.id}">✕</button>
      </div>
    `).join('');
    grid.querySelectorAll('[data-remove-existing]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const ok = await UI.confirmDialog('¿Eliminar esta foto de la parcela?', { danger: true });
        if (!ok) return;
        await Api.eliminarFoto(parcela.id, btn.dataset.removeExisting);
        btn.closest('.photo-thumb').remove();
        UI.toast('Foto eliminada.', 'info');
      });
    });
  }

  function renderNewPhotos() {
    const grid = document.getElementById('newPhotoGrid');
    grid.innerHTML = pendingPhotos.map((file, i) => `
      <div class="photo-thumb">
        <img src="${URL.createObjectURL(file)}" alt="nueva foto" />
        <button type="button" class="rm" data-remove-new="${i}">✕</button>
      </div>
    `).join('');
    grid.querySelectorAll('[data-remove-new]').forEach((btn) => {
      btn.addEventListener('click', () => { pendingPhotos.splice(Number(btn.dataset.removeNew), 1); renderNewPhotos(); });
    });
  }

  function bindEvents(container, parcela) {
    document.getElementById('btnNext').addEventListener('click', () => { if (validateCurrentStep()) showStep(stepIndex + 1); });
    document.getElementById('btnPrev').addEventListener('click', () => showStep(stepIndex - 1));
    document.getElementById('btnCancel').addEventListener('click', () => App.navigate('mis-parcelas'));

    document.getElementById('btnGeo').addEventListener('click', () => {
      const status = document.getElementById('geoStatus');
      if (!navigator.geolocation) { status.textContent = 'Tu navegador no soporta geolocalización.'; return; }
      status.textContent = 'Obteniendo ubicación…';
      navigator.geolocation.getCurrentPosition((pos) => {
        document.getElementById('latitud').value = pos.coords.latitude.toFixed(6);
        document.getElementById('longitud').value = pos.coords.longitude.toFixed(6);
        document.getElementById('gpsPrecision').value = Math.round(pos.coords.accuracy);
        if (pos.coords.altitude) document.getElementById('altitud').value = Math.round(pos.coords.altitude);
        status.textContent = `✅ Ubicación capturada (precisión ±${Math.round(pos.coords.accuracy)} m)`;
      }, (err) => {
        status.textContent = `No se pudo obtener la ubicación (${err.message}). Puedes ingresarla manualmente.`;
      }, { enableHighAccuracy: true, timeout: 12000 });
    });

    document.getElementById('photoInput').addEventListener('change', (e) => {
      pendingPhotos = pendingPhotos.concat(Array.from(e.target.files || []));
      renderNewPhotos();
      e.target.value = '';
    });

    document.getElementById('parcelaForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!validateCurrentStep()) return;
      const payload = collectForm();
      const btn = document.getElementById('btnSave');
      btn.disabled = true; btn.textContent = 'Guardando…';
      try {
        let saved;
        if (editingId) {
          const r = await Api.actualizarParcela(editingId, payload);
          saved = r.parcela;
        } else {
          const r = await Api.crearParcela(payload);
          saved = r.parcela;
        }
        if (pendingPhotos.length) {
          const fd = new FormData();
          pendingPhotos.forEach((f) => fd.append('fotos', f));
          await Api.subirFotos(saved.id, fd);
        }
        UI.toast(editingId ? 'Parcela actualizada correctamente.' : `Parcela guardada (código ${saved.codigo}).`, 'success');
        App.navigate('mis-parcelas');
      } catch (err) {
        UI.toast(err.message || 'No se pudo guardar la parcela.', 'error');
      } finally {
        btn.disabled = false; btn.textContent = '💾 Guardar parcela';
      }
    });
  }

  function collectForm() {
    const val = (id) => document.getElementById(id).value;
    return {
      nombreParcela: val('nombreParcela'), departamento: val('departamento'), municipio: val('municipio'),
      comunidad: val('comunidad'), fechaRegistro: val('fechaRegistro'), propietario: val('propietario'),
      telefono: val('telefono'), tenenciaTierra: val('tenenciaTierra'), numFamiliasBeneficiadas: val('numFamiliasBeneficiadas'),
      latitud: val('latitud'), longitud: val('longitud'), altitud: val('altitud'), gpsPrecision: val('gpsPrecision'),
      areaHa: val('areaHa'), estado: val('estado'), usoActual: val('usoActual'), cultivoPrincipal: val('cultivoPrincipal'),
      tipoSuelo: val('tipoSuelo'), pendiente: val('pendiente'), agua: val('agua'), fuenteAgua: val('fuenteAgua'),
      riesgoErosion: val('riesgoErosion'), sistemaRiego: val('sistemaRiego'), profundidadSuelo: val('profundidadSuelo'),
      talpetate: normalizeSiNo(val('talpetate')), encharca: normalizeSiNo(val('encharca')), bioindicadores: val('bioindicadores'),
      lluviaAnual: val('lluviaAnual'), lluviaFuente: val('lluviaFuente'), intervenciones: val('intervenciones'),
      especiesReforestacion: val('especiesReforestacion'), fechaProximaVisita: val('fechaProximaVisita'),
      consentimientoProductor: document.getElementById('consentimientoProductor').checked,
      observaciones: val('observaciones'),
    };
  }
  function normalizeSiNo(v) { return v === 'Sin evaluar' ? '' : v; }

  /* ===== Listado "Mis parcelas" ===== */
  async function renderMisParcelas(container) {
    container.innerHTML = `<div class="loading-spin"></div>`;
    const { parcelas } = await Api.listarParcelas();
    if (!parcelas.length) {
      container.innerHTML = `
        <div class="panel glass empty-state">
          <p style="font-size:15px;color:var(--text);margin-bottom:10px;">Aún no has registrado ninguna parcela.</p>
          <button class="btn btn-primary" id="btnFirstParcela">+ Registrar mi primera parcela</button>
        </div>`;
      document.getElementById('btnFirstParcela').addEventListener('click', () => App.navigate('nueva-parcela'));
      return;
    }
    container.innerHTML = `<div class="parcela-card-list">${parcelas.map(cardHtml).join('')}</div>`;
    container.querySelectorAll('.parcela-card').forEach((card) => {
      card.addEventListener('click', () => App.navigate('editar-parcela', { editingId: card.dataset.id }));
    });
  }

  function cardHtml(p) {
    return `
      <div class="parcela-card glass" data-id="${p.id}">
        <h4>${UI.escapeHtml(p.nombreParcela)}</h4>
        <div class="pc-meta">${UI.escapeHtml(p.codigo)} · ${UI.escapeHtml(p.departamento)} / ${UI.escapeHtml(p.municipio)}</div>
        <div class="pc-meta">${UI.fmtHa(p.areaHa)} · Registrado ${UI.fmtFecha(p.fechaRegistro)}</div>
        ${p.fotos && p.fotos.length ? `<div class="hint">📷 ${p.fotos.length} foto(s)</div>` : '<div class="hint">Sin fotos aún</div>'}
        <div class="pc-tags">${UI.estadoTagHtml(p.estado)} ${UI.validacionTagHtml(p.estadoValidacion)}</div>
        ${p.comentarioSupervisor ? `<div class="hint" style="margin-top:8px;">💬 ${UI.escapeHtml(p.comentarioSupervisor)}</div>` : ''}
      </div>
    `;
  }

  return { render, renderMisParcelas };
})();
