// ── MÓDULOS DE SISTEMA: IMPORTAR DATOS · USUARIOS Y PERMISOS ──

const MODULOS_UI = [
  ['ejecucion', 'Ejecución', 'Cantidades y fechas entregadas'],
  ['programacion', 'Programación', 'Cantidades y fechas programadas'],
  ['conred', 'CONRED', 'Solicitudes y programación CONRED'],
  ['bodegas', 'Bodegas', 'Inventarios y convenios por bodega']
];
const MODALIDADES_UI = [['nda', 'NDA'], ['mc', 'MC'], ['judicial', 'Judicial'], ['insan', 'INSAN'], ['apa', 'APA'], ['reserva', 'Reserva']];
const TOTALES_UI = [
  ['AA · NDA', 'nda'], ['AA · MC', 'mc'], ['AA · Judicial', 'judicial'], ['AA · INSAN', 'insan'], ['APA', 'apa'], ['Reserva', 'reserva']
];
const hoyISO = () => { const d = new Date(); return new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };

/* ═════════════ IMPORTAR DATOS ═════════════ */
let impPreview = null;

function initImportPage() {
  const root = document.getElementById('imp-root');
  if (!currentUser.permisos.importar) {
    root.innerHTML = '<div class="placeholder-page"><div class="ph-icon">🔒</div><div class="ph-title">Sin permiso</div><div class="ph-sub">Solicita al administrador el permiso para importar datos.</div></div>';
    return;
  }
  impPreview = null;
  root.innerHTML = `
    <div class="imp-card">
      <div class="imp-hd"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z"/><path d="M14 3v5h5M12 17v-6M9.5 13.5L12 11l2.5 2.5"/></svg>Importar datos desde Excel</div>
      <div class="imp-body">
        <label class="imp-drop" id="imp-drop">
          <input type="file" id="imp-file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" hidden>
          <span class="imp-drop-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a4.5 4.5 0 01-.6-8.96A6 6 0 0118 8.5a4 4 0 01-1 7.9M12 12v8M9 15l3-3 3 3"/></svg></span>
          <strong id="imp-drop-title">Arrastra tu archivo aquí</strong>
          <span id="imp-drop-sub">o haz clic para seleccionar</span>
          <small>Formato: .xlsx · máximo 15 MB</small>
        </label>
        <div class="imp-type-lbl">Tipo de datos a importar</div>
        <div class="imp-types">
          <div class="imp-type on">
            <span class="imp-type-check" aria-hidden="true">✓</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M3 14h18M9 4v16"/></svg>
            <strong>Matriz de seguimiento</strong>
            <small>Hoja "MATRIZ" · Programación DAAN Plan de Acción de Emergencia</small>
          </div>
        </div>
        <p class="imp-help">Usa siempre el mismo formato de Excel; el sistema solo actualiza la información. Antes de aplicar se muestra una vista previa con los cambios.</p>
        <div id="imp-preview"></div>
      </div>
    </div>
    <div class="card imp-history"><div class="card-hd"><div class="card-heading"><span class="card-icon">↺</span><div><div class="card-hd-title">Historial de importaciones</div><div class="card-hd-sub">Últimas cargas de la matriz</div></div></div></div><div id="imp-history" class="imp-history-list"><div class="empty-state">Cargando…</div></div></div>`;

  const drop = document.getElementById('imp-drop'), input = document.getElementById('imp-file');
  input.addEventListener('change', () => input.files[0] && previewImport(input.files[0]));
  ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
  ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
  drop.addEventListener('drop', e => { const f = e.dataTransfer.files[0]; if (f) previewImport(f); });
  loadImportHistory();
}

async function loadImportHistory() {
  const box = document.getElementById('imp-history');
  try {
    const { historial } = await api('api/import.php?a=historial');
    box.innerHTML = historial.length ? historial.map(h => `<div class="imp-hist-row"><span class="imp-hist-dot"></span><div><strong>${escHTML(h.detalle)}</strong><small>${escHTML(h.fecha)} · ${escHTML(h.usuario)}</small></div></div>`).join('')
      : '<div class="empty-state">Aún no hay importaciones desde el sistema. La matriz inicial corresponde al archivo del 23 de septiembre.</div>';
  } catch (e) {
    box.innerHTML = `<div class="empty-state">${escHTML(e.message)}</div>`;
  }
}

async function previewImport(file) {
  const out = document.getElementById('imp-preview');
  const drop = document.getElementById('imp-drop');
  if (!/\.xlsx$/i.test(file.name)) { out.innerHTML = `<div class="imp-msg err">Solo se aceptan archivos Excel .xlsx.</div>`; return; }
  document.getElementById('imp-drop-title').textContent = file.name;
  document.getElementById('imp-drop-sub').textContent = 'Analizando archivo…';
  drop.classList.add('busy');
  out.innerHTML = '';
  const form = new FormData();
  form.append('archivo', file);
  try {
    impPreview = await api('api/import.php?a=previsualizar', { form });
    renderImportPreview();
    document.getElementById('imp-drop-sub').textContent = 'Clic para elegir otro archivo';
  } catch (e) {
    impPreview = null;
    out.innerHTML = `<div class="imp-msg err"><strong>No se pudo leer el archivo.</strong> ${escHTML(e.message)}</div>`;
    document.getElementById('imp-drop-sub').textContent = 'Clic para elegir otro archivo';
  } finally {
    drop.classList.remove('busy');
    document.getElementById('imp-file').value = '';
  }
}

function renderImportPreview() {
  const p = impPreview, a = p.totales_actuales, n = p.totales_nuevos;
  const diff = v => v === 0 ? '<span class="rx-muted">—</span>' : `<span class="${v > 0 ? 'up' : 'down'}">${v > 0 ? '+' : ''}${fmtN(v)}</span>`;
  const rowsFor = pre => TOTALES_UI.map(([l, k]) => `<tr><td>${l}</td><td>${fmtN(a[pre + k])}</td><td><strong>${fmtN(n[pre + k])}</strong></td><td>${diff(n[pre + k] - a[pre + k])}</td></tr>`).join('');
  const sum = (t, pre) => ['nda', 'mc', 'judicial', 'insan', 'apa', 'reserva'].reduce((s, k) => s + t[pre + k], 0);
  document.getElementById('imp-preview').innerHTML = `
    <div class="imp-prev">
      <div class="imp-prev-hd">
        <div><span class="eyebrow">VISTA PREVIA</span><h3>${escHTML(p.archivo)}</h3><small>Hoja «${escHTML(p.hoja)}» · ${fmtN(p.filas)} municipios en ${p.departamentos} departamentos</small></div>
        <div class="imp-stats">
          <span><b>${p.cambiados}</b>con cambios</span>
          <span><b>${p.nuevos}</b>nuevos</span>
          <span class="${p.eliminados.length ? 'warn' : ''}"><b>${p.eliminados.length}</b>ya no están</span>
        </div>
      </div>
      <div class="imp-cols">
        <table class="imp-tbl"><thead><tr><th>Ejecutado</th><th>Actual</th><th>Nuevo</th><th>Diferencia</th></tr></thead><tbody>${rowsFor('ej_')}<tr class="tot"><td>Total ejecutado</td><td>${fmtN(sum(a, 'ej_'))}</td><td><strong>${fmtN(sum(n, 'ej_'))}</strong></td><td>${diff(sum(n, 'ej_') - sum(a, 'ej_'))}</td></tr></tbody></table>
        <table class="imp-tbl"><thead><tr><th>Programado</th><th>Actual</th><th>Nuevo</th><th>Diferencia</th></tr></thead><tbody>${rowsFor('prog_')}<tr class="tot"><td>Total programado</td><td>${fmtN(sum(a, 'prog_'))}</td><td><strong>${fmtN(sum(n, 'prog_'))}</strong></td><td>${diff(sum(n, 'prog_') - sum(a, 'prog_'))}</td></tr></tbody></table>
      </div>
      ${p.eliminados.length ? `<div class="imp-msg warn"><strong>Municipios que no vienen en el archivo y se quitarán:</strong> ${p.eliminados.map(escHTML).join(', ')}</div>` : ''}
      ${p.sin_color ? `<div class="imp-msg warn">${p.sin_color} municipios sin color de riesgo reconocible en la columna Municipio; se conserva su clasificación anterior.</div>` : ''}
      ${p.avisos.length ? `<details class="imp-msg warn"><summary>${p.avisos.length} avisos de lectura</summary><ul>${p.avisos.map(x => `<li>${escHTML(x)}</li>`).join('')}</ul></details>` : ''}
      <div class="imp-confirm">
        <label>Fecha de corte de los datos<input type="date" id="imp-fecha" class="fi" value="${p.fecha_corte_excel || hoyISO()}" max="${hoyISO()}"></label>
        <p>Al confirmar se <strong>reemplaza toda la matriz</strong> con este archivo, incluidas las ediciones hechas a mano en el sistema. Queda registrado en la bitácora.</p>
        <div class="imp-actions">
          <button class="glass-btn" onclick="initImportPage()">Cancelar</button>
          <button class="auth-submit-btn" id="imp-go" onclick="confirmImport()">Confirmar importación</button>
        </div>
      </div>
    </div>`;
}

async function confirmImport() {
  if (!impPreview) return;
  const fecha = document.getElementById('imp-fecha').value;
  if (!fecha) { showToast('⚠️ Indica la fecha de corte.', 'error'); return; }
  const btn = document.getElementById('imp-go');
  btn.disabled = true; btn.textContent = 'Importando…';
  try {
    const r = await api('api/import.php?a=confirmar', { json: { token: impPreview.token, fecha_corte: fecha } });
    document.getElementById('imp-preview').innerHTML = `<div class="imp-msg ok"><strong>✓ Importación completada.</strong> ${fmtN(r.filas)} municipios actualizados. Recargando tablero…</div>`;
    setTimeout(() => { location.hash = ''; location.reload(); }, 1400);
  } catch (e) {
    btn.disabled = false; btn.textContent = 'Confirmar importación';
    showToast(`No se importó: ${e.message}`, 'error');
  }
}

/* ═════════════ USUARIOS Y PERMISOS ═════════════ */
let usrState = { usuarios: [], departamentos: [], tab: 'usuarios', editing: null };

async function initUsersPage() {
  const root = document.getElementById('usr-root');
  if (currentUser.rol !== 'admin') {
    root.innerHTML = '<div class="placeholder-page"><div class="ph-icon">🔒</div><div class="ph-title">Solo administradores</div></div>';
    return;
  }
  root.innerHTML = `
    <div class="usr-top">
      <div class="segmented" role="tablist">
        <button id="usr-tab-usuarios" onclick="setUsersTab('usuarios')">Usuarios</button>
        <button id="usr-tab-bitacora" onclick="setUsersTab('bitacora')">Bitácora</button>
      </div>
      <button class="auth-submit-btn usr-new" id="usr-new" onclick="openUserModal()">+ Nuevo usuario</button>
    </div>
    <div id="usr-body"><div class="empty-state">Cargando…</div></div>`;
  setUsersTab(usrState.tab);
}

async function setUsersTab(tab) {
  usrState.tab = tab;
  ['usuarios', 'bitacora'].forEach(t => document.getElementById('usr-tab-' + t).classList.toggle('active', t === tab));
  document.getElementById('usr-new').hidden = tab !== 'usuarios';
  const body = document.getElementById('usr-body');
  try {
    if (tab === 'usuarios') {
      const r = await api('api/users.php?a=listar');
      usrState.usuarios = r.usuarios; usrState.departamentos = r.departamentos;
      renderUsersTable();
    } else {
      const r = await api('api/users.php?a=bitacora');
      body.innerHTML = `<div class="card"><div class="usr-tbl-wrap"><table class="usr-tbl"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead><tbody>${
        r.registros.map(x => `<tr><td class="nowrap">${escHTML(x.fecha)}</td><td>${escHTML(x.usuario || '—')}</td><td><span class="usr-act act-${escHTML(x.accion)}">${escHTML(x.accion.replace(/_/g, ' '))}</span></td><td class="usr-det">${escHTML(x.detalle || '')}</td></tr>`).join('') || '<tr><td colspan="4" class="rx-empty">Sin registros.</td></tr>'
      }</tbody></table></div></div>`;
    }
  } catch (e) {
    body.innerHTML = `<div class="imp-msg err">${escHTML(e.message)}</div>`;
  }
}

function permSummary(u) {
  if (u.rol === 'admin') return '<span class="usr-perm">Acceso total</span>';
  if (u.rol === 'visualizador') return '<span class="usr-perm muted">Solo consulta</span>';
  const p = u.permisos, chips = [];
  p.modulos.forEach(m => chips.push(MODULOS_UI.find(x => x[0] === m)[1]));
  const mods = p.modalidades.length === 6 ? 'Todas las modalidades' : (p.modalidades.map(m => MODALIDADES_UI.find(x => x[0] === m)[1]).join(', ') || 'Sin modalidades');
  const deps = p.departamentos.length ? `${p.departamentos.length} depto${p.departamentos.length > 1 ? 's' : ''}` : 'Todos los deptos';
  return `<span class="usr-perm">${chips.join(' · ') || 'Sin módulos'}</span><small>${mods} · ${deps}${p.importar ? ' · Importa Excel' : ''}</small>`;
}

function renderUsersTable() {
  const rows = usrState.usuarios.map(u => `
    <tr>
      <td><div class="usr-name"><span class="usr-av">${escHTML(u.nombre.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase())}</span><span><strong>${escHTML(u.nombre)}</strong><small>@${escHTML(u.usuario)}</small></span></div></td>
      <td><span class="top-role-badge ${ROL_INFO[u.rol].cls}">${ROL_INFO[u.rol].badge}</span></td>
      <td class="usr-perms">${permSummary(u)}</td>
      <td>${u.activo ? '<span class="usr-state on">Activo</span>' : '<span class="usr-state off">Inactivo</span>'}</td>
      <td class="nowrap">${u.ultimo_acceso ? escHTML(u.ultimo_acceso) : '<span class="rx-muted">Nunca</span>'}</td>
      <td><button class="muni-edit-action-btn" onclick="openUserModal(${u.id})">✏️ Editar</button></td>
    </tr>`).join('');
  document.getElementById('usr-body').innerHTML = `<div class="card"><div class="usr-tbl-wrap"><table class="usr-tbl"><thead><tr><th>Usuario</th><th>Rol</th><th>Permisos delegados</th><th>Estado</th><th>Último acceso</th><th></th></tr></thead><tbody>${rows}</tbody></table></div></div>
    <p class="rx-note"><strong>Administrador:</strong> todo, incluidos usuarios e importación. <strong>Editor:</strong> solo edita lo que se le delega (módulos, modalidades y departamentos). <strong>Visualizador:</strong> solo consulta.</p>`;
}

function ensureUserModal() {
  if (document.getElementById('user-modal')) return;
  const el = document.createElement('div');
  el.className = 'modal-backdrop'; el.id = 'user-modal'; el.hidden = true;
  el.innerHTML = `<div class="edit-modal-card usr-modal" role="dialog" aria-modal="true" aria-labelledby="usr-modal-title">
    <div class="edit-modal-hd"><div><div class="edit-modal-title" id="usr-modal-title">Usuario</div><div class="edit-modal-sub" id="usr-modal-sub"></div></div><button class="edit-close-btn" onclick="closeUserModal()" aria-label="Cerrar">✕</button></div>
    <div class="usr-form" id="usr-form"></div>
    <div class="usr-error" id="usr-error" role="alert"></div>
    <div class="edit-modal-actions"><button class="glass-btn" onclick="closeUserModal()">Cancelar</button><button class="auth-submit-btn" id="usr-save" style="width:auto;padding:8px 18px" onclick="saveUser()">Guardar</button></div>
  </div>`;
  document.body.appendChild(el);
}

function openUserModal(id) {
  ensureUserModal();
  const u = id ? usrState.usuarios.find(x => x.id === id) : { id: 0, usuario: '', nombre: '', rol: 'editor', activo: true, permisos: { modulos: ['ejecucion', 'programacion'], modalidades: MODALIDADES_UI.map(m => m[0]), departamentos: [], importar: false } };
  usrState.editing = u;
  const p = u.rol === 'editor' ? u.permisos : { modulos: ['ejecucion', 'programacion'], modalidades: MODALIDADES_UI.map(m => m[0]), departamentos: [], importar: false };
  const self = u.id === currentUser.id;
  document.getElementById('usr-modal-title').textContent = id ? 'Editar usuario' : 'Nuevo usuario';
  document.getElementById('usr-modal-sub').textContent = id ? `@${u.usuario}` : 'Crea el acceso y define qué puede hacer';
  const chk = (name, val, on, label, sub = '') => `<label class="usr-chk"><input type="checkbox" name="${name}" value="${val}"${on ? ' checked' : ''}><span><strong>${label}</strong>${sub ? `<small>${sub}</small>` : ''}</span></label>`;
  document.getElementById('usr-form').innerHTML = `
    <div class="edit-grid">
      <div class="edit-field-group"><div class="edit-field-label"><span>Nombre completo</span></div><input class="edit-input" id="uf-nombre" value="${escHTML(u.nombre)}" maxlength="80" autocomplete="off"></div>
      <div class="edit-field-group"><div class="edit-field-label"><span>Usuario</span></div><input class="edit-input" id="uf-usuario" value="${escHTML(u.usuario)}" maxlength="40" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="ej. miguel.lopez"></div>
      <div class="edit-field-group" style="grid-column:1/-1"><div class="edit-field-label"><span>${id ? 'Nueva contraseña (déjala vacía para no cambiarla)' : 'Contraseña'}</span></div>
        <div class="usr-pass"><input class="edit-input" id="uf-pass" type="text" autocomplete="new-password" spellcheck="false" placeholder="Mínimo 8 caracteres"><button type="button" class="glass-btn" onclick="genPassword()">Generar</button></div></div>
    </div>
    <div class="usr-sec">Rol</div>
    <div class="usr-roles">
      ${['admin', 'editor', 'visualizador'].map(r => `<label class="usr-role"><input type="radio" name="uf-rol" value="${r}"${u.rol === r ? ' checked' : ''}${self && r !== 'admin' ? ' disabled' : ''}><span><strong>${ROL_INFO[r].badge}</strong><small>${ROL_INFO[r].desc}</small></span></label>`).join('')}
    </div>
    <div id="uf-perms"${u.rol === 'editor' ? '' : ' hidden'}>
      <div class="usr-sec">¿Qué puede editar?</div>
      <div class="usr-grid">${MODULOS_UI.map(([k, l, s]) => chk('uf-mod', k, p.modulos.includes(k), l, s)).join('')}</div>
      <div class="usr-sec">Modalidades <small>(aplica a Ejecución y Programación)</small></div>
      <div class="usr-grid six">${MODALIDADES_UI.map(([k, l]) => chk('uf-modal', k, p.modalidades.includes(k), l)).join('')}</div>
      <div class="usr-sec">Departamentos</div>
      <div class="usr-roles two">
        <label class="usr-role"><input type="radio" name="uf-dscope" value="all"${p.departamentos.length ? '' : ' checked'}><span><strong>Todos</strong><small>Puede editar cualquier departamento</small></span></label>
        <label class="usr-role"><input type="radio" name="uf-dscope" value="some"${p.departamentos.length ? ' checked' : ''}><span><strong>Solo algunos</strong><small>Elige los departamentos abajo</small></span></label>
      </div>
      <div class="usr-grid deps" id="uf-deps"${p.departamentos.length ? '' : ' hidden'}>${usrState.departamentos.map(d => chk('uf-dep', d, p.departamentos.includes(d), escHTML(d))).join('')}</div>
      <div class="usr-sec">Datos</div>
      <div class="usr-grid">${chk('uf-imp', '1', p.importar, 'Puede importar el Excel', 'Reemplaza la matriz completa con un archivo nuevo')}</div>
    </div>
    <label class="usr-chk usr-active"><input type="checkbox" id="uf-activo"${u.activo ? ' checked' : ''}${self ? ' disabled' : ''}><span><strong>Cuenta activa</strong><small>Si la desactivas, no podrá ingresar</small></span></label>`;
  const form = document.getElementById('usr-form');
  form.querySelectorAll('input[name="uf-rol"]').forEach(r => r.addEventListener('change', () => { document.getElementById('uf-perms').hidden = r.value !== 'editor' || !r.checked; }));
  form.querySelectorAll('input[name="uf-dscope"]').forEach(r => r.addEventListener('change', () => { document.getElementById('uf-deps').hidden = form.querySelector('input[name="uf-dscope"]:checked').value !== 'some'; }));
  document.getElementById('usr-error').textContent = '';
  document.getElementById('user-modal').hidden = false;
  document.getElementById(id ? 'uf-nombre' : 'uf-nombre').focus();
}

function closeUserModal() { document.getElementById('user-modal').hidden = true; usrState.editing = null; }

function genPassword() {
  const abc = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
  const buf = crypto.getRandomValues(new Uint32Array(12));
  document.getElementById('uf-pass').value = Array.from(buf, n => abc[n % abc.length]).join('');
}

async function saveUser() {
  const u = usrState.editing; if (!u) return;
  const form = document.getElementById('usr-form');
  const vals = name => [...form.querySelectorAll(`input[name="${name}"]:checked`)].map(i => i.value);
  const rol = form.querySelector('input[name="uf-rol"]:checked').value;
  const allDeps = form.querySelector('input[name="uf-dscope"]:checked').value === 'all';
  const payload = {
    id: u.id,
    nombre: document.getElementById('uf-nombre').value.trim(),
    usuario: document.getElementById('uf-usuario').value.trim().toLowerCase(),
    password: document.getElementById('uf-pass').value,
    rol,
    activo: document.getElementById('uf-activo').checked,
    permisos: { modulos: vals('uf-mod'), modalidades: vals('uf-modal'), departamentos: allDeps ? [] : vals('uf-dep'), importar: vals('uf-imp').length > 0 }
  };
  const err = document.getElementById('usr-error');
  if (rol === 'editor' && !allDeps && !payload.permisos.departamentos.length) { err.textContent = 'Elige al menos un departamento o marca «Todos».'; return; }
  if (rol === 'editor' && (payload.permisos.modulos.includes('ejecucion') || payload.permisos.modulos.includes('programacion')) && !payload.permisos.modalidades.length) { err.textContent = 'Elige al menos una modalidad para Ejecución/Programación.'; return; }
  const btn = document.getElementById('usr-save'); btn.disabled = true;
  try {
    await api('api/users.php?a=guardar', { json: payload });
    const pass = payload.password;
    closeUserModal();
    showToast(pass ? `✓ Usuario @${payload.usuario} guardado. Comparte la contraseña de forma segura.` : `✓ Usuario @${payload.usuario} guardado.`);
    setUsersTab('usuarios');
  } catch (e) {
    err.textContent = e.message;
  } finally {
    btn.disabled = false;
  }
}

Object.assign(window, { initImportPage, confirmImport, initUsersPage, setUsersTab, openUserModal, closeUserModal, genPassword, saveUser });
