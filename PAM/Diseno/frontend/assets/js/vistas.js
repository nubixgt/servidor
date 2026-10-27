/* =====================================================================
   Vistas del sistema conectadas a la API
   ===================================================================== */
let CAT = null;                      // catálogos (/catalogos)
const fd = s => s ? s.slice(0, 10).split('-').reverse().join('/') : '—';
const fdl = s => s ? new Date(s.slice(0, 10) + 'T12:00:00').toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' }) : '—';
const fts = s => s ? new Date(s).toLocaleString('es-GT', { dateStyle: 'short', timeStyle: 'short' }) : '—';
const hoyISO = () => new Date(Date.now() - new Date().getTimezoneOffset() * 6e4).toISOString().slice(0, 10);

/* parámetros de la API a partir de los filtros del tablero */
function filtrosApi(extra = {}) {
  const p = {};
  if (F.dep != null) p.dep = F.dep + 1;
  if (F.mun != null) p.mun = parseInt(MU[F.mun], 10);
  if (F.y != null) { const y = YS[F.y]; p.anio = y === 's/f' ? 0 : +y; }
  for (const k of ['e', 'r', 'n', 'v', 's', 'f', 'a', 'am', 't']) if (F[k] != null) p[k] = F[k];
  return { ...p, ...extra };
}

/* ---------- navegación con permisos ---------- */
const NAV = [
  ['Monitoreo', [['dashboard', 'Dashboard', 'grid', 'dashboard.ver'], ['territorio', 'Territorio', 'map', 'dashboard.ver'], ['alertas', 'Alertas', 'alert', 'alertas.ver']]],
  ['Gestión', [['beneficiarios', 'Beneficiarios', 'users', 'beneficiarios.ver'], ['solicitudes', 'Solicitudes', 'file', 'dashboard.ver'], ['visitas', 'Visitas', 'pin', 'dashboard.ver'],
    ['resoluciones', 'Resoluciones', 'stamp', 'dashboard.ver'], ['nomina', 'Nómina', 'wallet', 'dashboard.ver'], ['estados', 'Estados', 'pulse', 'dashboard.ver']]],
  ['Control', [['reportes', 'Reportes', 'chart', 'reportes.ver'], ['calidad', 'Calidad de datos', 'shield', 'calidad.ver'], ['importaciones', 'Importaciones', 'upload', 'importacion.ejecutar'],
    ['admin', 'Administración', 'sliders', ['usuarios.gestionar', 'roles.gestionar', 'auditoria.ver', 'catalogos.gestionar']]]]
];
const puedeVer = req => Array.isArray(req) ? req.some(PUEDE) : PUEDE(req);
function renderNav() {
  const crit = AG ? AG.al.qa : 0;
  $('#nav').innerHTML = NAV.map(([g, items]) => {
    const vis = items.filter(it => puedeVer(it[3]));
    if (!vis.length) return '';
    return `<div class="navgrp">${g}</div>` + vis.map(([k, l, i]) => `<button class="nav ${UI.view === k ? 'on' : ''}" data-view="${k}" title="${l}" ${UI.view === k ? 'aria-current="page"' : ''}>${ic(i, 19)}<span class="navl">${l}</span>${k === 'alertas' && crit ? `<span class="pill">${fmt(crit)}</span>` : ''}</button>`).join('');
  }).join('');
  document.querySelectorAll('.nav').forEach(b => b.onclick = () => { go(b.dataset.view); $('#side').classList.remove('open'); });
}
function header(eyebrow, title, desc) {
  return `<div class="vh"><div><div class="eyebrow">${eyebrow}</div><h1>${title}</h1>${desc ? `<p>${desc}</p>` : ''}</div><div class="meta">Corte de datos <b>${CORTE || '—'}</b><br>${fmt(TOTAL_NAC)} expedientes en su ámbito</div></div>`;
}
const VIEWS = {
  dashboard: () => header('Programa del Adulto Mayor', 'Centro de Monitoreo Nacional', '') + kpiRow() +
    `<div class="grid g2">${mapCardV2()}${estadoCard()}</div>` +
    `<div class="grid g2e">${flowCard()}${evoCard()}</div>` + timesCard() +
    `<div class="grid g2e">${antigCard()}${ageCard()}</div>` +
    `<div class="grid g2e">${alertsCard()}${terrCard()}</div>`,
  territorio: () => header('Territorio', 'Análisis territorial', 'Nacional → departamento → municipio. Seleccione un departamento en el mapa o en el ranking.') + mapCardV2(true) + terrCard(),
  alertas: () => header('Alertas', 'Requieren atención', `${RULES.length} reglas automáticas. Cada una abre el listado de casos con el filtro aplicado.`) + `<div class="card">${alertRows()}</div>`,
  solicitudes: () => header('Gestión', 'Solicitudes', 'Recepción, bandejas por etapa, antigüedad y re-solicitudes de la misma persona.') +
    (PUEDE('solicitud.registrar') ? `<div><button class="btn pri" data-accion="nueva-solicitud">${ic('file', 16)} Registrar nueva solicitud</button></div>` : '') +
    `<div class="grid g2e">${antigCard()}${flowCard()}</div>` + evoCard(['sol'], 'Solicitudes recibidas'),
  visitas: () => header('Gestión', 'Visitas', 'Visitas registradas y tiempos del proceso. Las visitas se registran desde el Expediente 360°.') + `<div class="grid g2e">${evoCard(['vpos', 'vneg'], 'Visitas registradas')}${timesCard()}</div>`,
  resoluciones: () => header('Gestión', 'Resoluciones', 'Última resolución por familia y resoluciones emitidas por período.') + `<div class="grid g2e">${resCard()}${evoCard(['res'], 'Resoluciones emitidas')}</div>`,
  nomina: () => header('Gestión', 'Nómina', 'Beneficiarios activos, ingresos y salidas por período, distribución territorial y por edad.') + `<div class="grid g2e">${evoCard(['ing', 'sal'], 'Ingresos y salidas de nómina')}${ageCard()}</div>` + terrCard(),
  estados: () => header('Gestión', 'Estados', 'Estado actual, suspendidos, fallecidos, bloqueados y sus transiciones.') + `<div class="grid g2e">${estadoCard()}${resCard()}</div>` + evoCard(['susp', 'fall'], 'Suspensiones y fallecimientos registrados'),
  beneficiarios: () => viewBenef(),
  calidad: () => viewCalidad(),
  reportes: () => viewReportes(),
  importaciones: () => viewImportaciones(),
  admin: () => viewAdmin()
};

/* ---------- modal genérico ---------- */
function modal(titulo, cuerpo, { boton = 'Guardar', alGuardar, ancho } = {}) {
  cerrarModal();
  const w = document.createElement('div'); w.className = 'modal-w'; w.setAttribute('role', 'dialog'); w.setAttribute('aria-modal', 'true');
  w.innerHTML = `<form class="modal gl" style="${ancho ? 'width:min(' + ancho + 'px,100%)' : ''}" novalidate><h3>${titulo}</h3>${cuerpo}<div class="err" id="mErr" role="alert"></div><div class="acciones"><button type="button" class="btn" id="mCancel">${alGuardar ? 'Cancelar' : 'Cerrar'}</button>${alGuardar ? `<button class="btn pri" id="mOk" type="submit">${boton}</button>` : ''}</div></form>`;
  document.body.appendChild(w);
  const f = w.querySelector('form');
  w.querySelector('#mCancel').onclick = cerrarModal;
  w.onclick = e => { if (e.target === w) cerrarModal(); };
  f.onsubmit = async e => {
    e.preventDefault(); if (!alGuardar) return;
    const b = w.querySelector('#mOk'); b.disabled = true; w.querySelector('#mErr').textContent = '';
    try { const datos = Object.fromEntries(new FormData(f).entries()); await alGuardar(datos, w); }
    catch (err) { w.querySelector('#mErr').textContent = err.message || 'No se pudo guardar'; b.disabled = false; }
  };
  const first = f.querySelector('input,select,textarea'); if (first) first.focus();
  return w;
}
function cerrarModal() { document.querySelectorAll('.modal-w').forEach(e => e.remove()); }
const campo = (n, l, t = 'text', extra = '') => `<div class="field ${extra.includes('full') ? 'full' : ''}"><label for="m_${n}">${l}</label>${t === 'textarea' ? `<textarea id="m_${n}" name="${n}"></textarea>` : `<input id="m_${n}" name="${n}" type="${t}" ${extra.replace('full', '')}>`}</div>`;
const selector = (n, l, opts, extra = '') => `<div class="field ${extra}"><label for="m_${n}">${l}</label><select id="m_${n}" name="${n}">${opts.map(([v, t]) => `<option value="${esc(v)}">${esc(t)}</option>`).join('')}</select></div>`;

/* ---------- Beneficiarios (búsqueda en servidor) ---------- */
const BEN = { cursores: [null], pagina: 0, clave: '', datos: null, cargando: false };
function viewBenef() {
  const rule = UI.bRule ? RULES.find(r => r.k === UI.bRule) : null;
  hook(() => {
    const i = $('#bq'); let t = 0;
    i.oninput = () => { clearTimeout(t); t = setTimeout(() => { UI.bq = i.value; UI.bSortManual = false; cargarBenef(true); }, 350); };
    const x = $('#clrRule'); if (x) x.onclick = () => { UI.bRule = null; renderView(); };
    const n = $('#btnNueva'); if (n) n.onclick = nuevaSolicitud;
    cargarBenef(false);
  });
  return header('Gestión', 'Beneficiarios', 'Búsqueda por DPI, número de expediente, nombre, teléfono o apoderado. Ignora tildes, mayúsculas y espacios extra. Los filtros del tablero también aplican.') +
    `<div class="card"><div class="ch"><div style="flex:1;min-width:220px"><div class="search" style="max-width:none"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg><input id="bq" type="search" value="${esc(UI.bq)}" placeholder="DPI, expediente, nombre, teléfono o apoderado" aria-label="Buscar en beneficiarios"></div></div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">${rule ? `<span class="chip">Alerta: ${rule.t}<button id="clrRule" aria-label="Quitar">${ic('close', 12)}</button></span>` : ''}
    ${PUEDE('solicitud.registrar') ? `<button class="btn pri" id="btnNueva">${ic('file', 16)} Nueva solicitud</button>` : ''}</div></div>
    <div id="benTabla"><div class="empty-state">Cargando expedientes…</div></div></div>`;
}
async function cargarBenef(reiniciar) {
  const rel = UI.bq && /[a-záéíóúñ]/i.test(UI.bq) && !UI.bSortManual;
  const params = filtrosApi({ q: UI.bq || undefined, regla: UI.bRule || undefined, orden: rel ? 'relevancia' : UI.bSort[0], dir: rel ? 'desc' : (UI.bSort[1] < 0 ? 'desc' : 'asc') });
  const clave = JSON.stringify(params);
  if (reiniciar || clave !== BEN.clave) { BEN.cursores = [null]; BEN.pagina = 0; BEN.clave = clave; BEN.total = null; }
  const box = $('#benTabla'); if (!box) return;
  try {
    const d = await api.get('/expedientes', { ...params, cursor: BEN.cursores[BEN.pagina] || undefined, limite: 25 });
    if (d.total != null) { BEN.total = d.total; BEN.minimo = d.total_es_minimo; }
    BEN.cursores[BEN.pagina + 1] = d.siguiente;
    pintarBenef(d);
  } catch (e) { box.innerHTML = `<div class="empty-state">${esc(e.message)}</div>`; }
}
function pintarBenef(d) {
  const box = $('#benTabla'); if (!box) return;
  const th = (k, l, n) => `<th class="${n ? 'n' : ''}">${k ? `<button data-bs="${k}">${l}${UI.bSort[0] === k ? ` <span class="so">${UI.bSort[1] < 0 ? '▼' : '▲'}</span>` : ''}</button>` : l}</th>`;
  const total = BEN.total == null ? '' : (BEN.minimo ? 'más de 10,000' : fmt(BEN.total));
  box.innerHTML = `<div class="tw"><table><thead><tr>${th('numero', 'Expediente')}${th('nombre', 'Nombre')}${th('', 'DPI')}${th('', 'Depto.')}${th('', 'Municipio')}${th('', 'Edad', 1)}${th('fsol', 'Solicitud')}${th('', 'Estado actual')}${th('', 'Nómina')}${th('actualizado', 'Actualizado')}<th></th></tr></thead>
    <tbody>${d.items.length ? d.items.map(p => `<tr><td class="tnum">${esc(p.numero || 's/n')}</td><td><b>${esc(p.nombre)}</b></td><td class="mask">${esc(p.dpi || '—')}</td><td>${esc(p.departamento)}</td><td>${esc(p.municipio)}</td><td class="n">${p.edad ?? '—'}</td><td class="tnum">${fd(p.fecha_solicitud)}</td><td>${badge(p.estado_orden)}</td><td>${nomBadge(p.situacion_nomina)}</td><td class="tnum">${fd(p.fecha_estado)}</td><td><button class="btn" data-open="${p.id}">Ver expediente</button></td></tr>`).join('') : `<tr><td colspan="11" class="empty">Sin resultados con estos filtros. Quite un filtro o pruebe otra búsqueda.</td></tr>`}</tbody></table></div>
    <div class="pager"><span>${total ? total + ' expedientes · ' : ''}página ${BEN.pagina + 1} · 25 por página · ${scopeName()}</span><span style="display:flex;gap:6px"><button class="btn" data-pg="-1" ${BEN.pagina ? '' : 'disabled'}>Anterior</button><button class="btn" data-pg="1" ${d.siguiente ? '' : 'disabled'}>Siguiente</button></span></div>`;
  box.querySelectorAll('[data-open]').forEach(b => b.onclick = () => openExp(+b.dataset.open));
  box.querySelectorAll('[data-pg]').forEach(b => b.onclick = () => { BEN.pagina += +b.dataset.pg; cargarBenef(false); });
  box.querySelectorAll('[data-bs]').forEach(b => b.onclick = () => { const k = b.dataset.bs; UI.bSortManual = true; UI.bSort = [k, UI.bSort[0] === k ? -UI.bSort[1] : (k === 'nombre' || k === 'numero' ? 1 : -1)]; cargarBenef(true); });
}

/* ---------- Nueva solicitud ---------- */
function nuevaSolicitud() {
  const u = api.usuario();
  const muns = CAT.municipios.filter(m => (!u.municipio_id || m.id === u.municipio_id) && (!u.departamento_id || m.departamento_id === u.departamento_id));
  const deps = Object.fromEntries(CAT.departamentos.map(d => [d.id, d.nombre]));
  modal('Registrar nueva solicitud', `<div class="form-grid">
    ${campo('nombre', 'Nombre completo', 'text', 'full required maxlength="200"')}
    ${campo('dpi', 'DPI / CUI (13 dígitos)', 'text', 'inputmode="numeric" maxlength="15"')}
    ${campo('fecha_nacimiento', 'Fecha de nacimiento', 'date', 'required')}
    ${campo('telefono', 'Teléfono', 'tel')}
    ${campo('fecha_solicitud', 'Fecha de solicitud', 'date', `required value="${hoyISO()}" max="${hoyISO()}"`)}
    ${selector('municipio_id', 'Municipio', muns.map(m => [m.id, `${m.nombre} · ${deps[m.departamento_id]}`]), 'full')}
    ${campo('direccion', 'Dirección', 'text', 'full maxlength="400"')}</div>
    <p class="note">El sistema valida el dígito verificador del CUI y evita registrar una segunda solicitud activa para la misma persona.</p>`,
    { boton: 'Registrar', ancho: 680, alGuardar: async d => {
      const r = await api.post('/expedientes', { ...d, municipio_id: +d.municipio_id, dpi: d.dpi || null, telefono: d.telefono || null, direccion: d.direccion || null });
      cerrarModal(); toast('Solicitud registrada'); openExp(r.id);
    } });
}

/* ---------- Expediente 360° ---------- */
let EXP = { d: null, id: null, tab: 'resumen', dpi: null };
async function openExp(id) {
  try { const d = await api.get('/expedientes/' + id); EXP = { d, id, tab: EXP.id === id ? EXP.tab : 'resumen', dpi: null }; drawExp(); }
  catch (e) { toast(e.message); }
}
function closeExp() { document.querySelectorAll('.scrim,.drawer').forEach(e => e.remove()); }
const COLOR_EV = { SOLICITUD: 'var(--blue5)', VISITA_POSITIVA: 'var(--ok)', VISITA_NEGATIVA: 'var(--warnf)', VISITA_NO_LOCALIZADO: 'var(--warnf)', OBSERVACION: 'var(--off)', RESOLUCION: 'var(--st3)', NOMINA_ALTA: 'var(--ok)', NOMINA_BAJA: 'var(--st3)', SUSPENSION: 'var(--st4)', FALLECIMIENTO: 'var(--dead)', ESTADO: 'var(--s7)' };
function timeline(d) {
  return `<div class="tl">${d.eventos.map(e => `<div class="ev" style="--c:${COLOR_EV[e.tipo] || 'var(--blue5)'}"><div class="et">${esc(e.titulo)}${e.origen === 'SISTEMA' ? ' · registrado en el sistema' : ''}</div><div class="ed">${fdl(e.fecha)}</div>${e.detalle ? `<div class="ex">${esc(e.detalle)}</div>` : ''}</div>`).join('')}</div>`;
}
function drawExp() {
  closeExp();
  const d = EXP.d, p = d.persona, x = d.expediente;
  const puedeGestionar = ['observacion.registrar', 'visita.registrar', 'resolucion.emitir', 'estado.cambiar', 'nomina.gestionar', 'beneficiarios.editar', 'alertas.gestionar'].some(PUEDE);
  const T = [['resumen', 'Resumen'], ['datos', 'Datos personales'], ['sol', 'Solicitud'], ['vis', 'Visitas'], ['res', 'Resoluciones'], ['nom', 'Nómina'], ['apo', 'Apoderado'], ['obs', 'Observaciones'], ['est', 'Estado'], ['his', 'Historial']];
  if (puedeGestionar) T.push(['ges', 'Gestionar']);
  const dpiTxt = EXP.dpi || p.dpi || '—';
  const kv = a => `<dl class="kv">${a.map(([k, v]) => `<dt>${k}</dt><dd>${v}</dd>`).join('')}</dl>`;
  const tbl = (h, rows) => rows.length ? `<div class="tw"><table><thead><tr>${h.map(t => `<th>${t}</th>`).join('')}</tr></thead><tbody>${rows.map(r => `<tr>${r.map(c => `<td class="wrap">${c ?? '—'}</td>`).join('')}</tr>`).join('')}</tbody></table></div>` : `<div class="empty">Sin registros.</div>`;
  const orig = o => o === 'SISTEMA' ? '<span class="badge" style="color:var(--blue7);background:var(--blue1)">Sistema</span>' : '<span class="note">Matriz</span>';
  let body = '';
  switch (EXP.tab) {
    case 'resumen': {
      const lv = d.visitas[0], lr = d.resoluciones[0];
      body = `<div class="sumgrid"><div class="sg"><small>Solicitud</small><b>${fd(x.fecha_solicitud)}</b></div><div class="sg"><small>Última visita</small><b>${lv ? lv.resultado + ' · ' + fd(lv.fecha) : 'Sin visita'}</b></div><div class="sg"><small>Última resolución</small><b>${lr ? lr.codigo + ' · ' + fd(lr.fecha) : 'Sin resolución'}</b></div><div class="sg"><small>Nómina</small><b>${x.fecha_nomina_desde ? 'Desde ' + fd(x.fecha_nomina_desde) : 'No ha ingresado'}</b></div></div>
      ${d.alertas.length ? `<h3 style="font:700 14px var(--f-disp);margin:0 0 8px">Alertas del expediente</h3><div class="alist" style="margin-bottom:18px">${d.alertas.map(a => { const r = RULES.find(z => z.k === a.regla) || { p: 'm', icon: 'alert', d: '' }; const caso = d.casos.find(c => c.regla === a.regla); return `<div class="al"><span class="band" style="background:${PRC[r.p]}"></span><span class="ai" style="color:${PRC[r.p]}">${ic(r.icon)}</span><div><div class="at">${a.titulo}<span class="pr ${r.p}">${PRN[r.p]}</span></div><div class="ad">${caso ? 'Revisión: ' + caso.estado.replace('_', ' ').toLowerCase() + (caso.comentario ? ' · ' + esc(caso.comentario) : '') : r.d}</div></div><span></span>${PUEDE('alertas.gestionar') || PUEDE('calidad.gestionar') ? `<button class="btn" data-caso="${a.regla}">Revisar</button>` : '<span></span>'}</div>`; }).join('')}</div>` : ''}
      ${d.otros_expedientes.length ? `<p class="note" style="margin:0 0 14px"><b>La persona tiene ${d.otros_expedientes.length} expediente(s) más:</b> ${d.otros_expedientes.map(o => `<button class="link" data-otro="${o.id}">${esc(o.numero || 's/n')} (${fd(o.fecha_solicitud)})</button>`).join(', ')}</p>` : ''}
      <h3 style="font:700 14px var(--f-disp);margin:0 0 8px">Última observación</h3><p style="margin:0 0 18px;color:var(--ink2)">${x.ult_observacion ? esc(x.ult_observacion) + ` <span class="note">· ${fd(x.ult_observacion_fecha)}</span>` : 'Sin observaciones.'}</p>
      <h3 style="font:700 14px var(--f-disp);margin:0 0 12px">Línea de tiempo</h3>${timeline(d)}`;
      break;
    }
    case 'datos': body = kv([['Nombre', esc(p.nombre)], ['Documento', `<span class="mask">${esc(dpiTxt)}</span> ${p.cui_valido ? '<span class="badge" style="color:var(--ok);background:var(--ok-bg)">CUI válido</span>' : `<span class="badge" style="color:var(--warn);background:var(--warn-bg)">${p.documento_tipo === 'CEDULA' ? 'Cédula / no CUI' : p.documento_tipo === 'CUI' ? 'CUI con verificador incorrecto' : 'Sin documento'}</span>`}`], ['Fecha de nacimiento', fd(p.fecha_nacimiento)], ['Edad calculada', p.edad != null ? p.edad + ' años' : '—'], ['Teléfono', p.telefono ? esc(p.telefono) : '<span class="note">No registrado</span>'], ['Dirección', esc(p.direccion || '—')], ['Departamento', esc(x.departamento)], ['Municipio', `${esc(x.municipio)} <span class="note">(${x.municipio_id})</span>`]]); break;
    case 'sol': body = kv([['No. expediente', esc(x.numero || 's/n')], ['Fecha de solicitud', fdl(x.fecha_solicitud)], ['Año de solicitud', x.fecha_solicitud ? x.fecha_solicitud.slice(0, 4) + ' <span class="note">(derivado de la fecha)</span>' : '—'], ['Días transcurridos', x.fecha_solicitud ? fmt((Date.now() - new Date(x.fecha_solicitud)) / 864e5) : '—']]); break;
    case 'vis': body = tbl(['Fecha', 'Resultado', 'Observación', 'Técnico', 'Origen'], d.visitas.map(v => [fd(v.fecha), v.resultado === 'POSITIVA' ? '<b style="color:var(--ok)">Positiva</b>' : `<b style="color:var(--warn)">${v.resultado === 'NEGATIVA' ? 'Negativa' : 'No localizado'}</b>`, esc(v.observacion || ''), esc(v.tecnico || ''), orig(v.origen)])); break;
    case 'res': body = tbl(['Fecha', 'Código', 'Tipo', 'Familia', 'Número', 'Origen'], d.resoluciones.map((r, i) => [fd(r.fecha) + (i === 0 ? ' <span class="badge" style="color:var(--blue7);background:var(--blue1)">Más reciente</span>' : ''), `<b>${esc(r.codigo)}</b>`, esc(r.tipo), esc(r.familia), esc(r.numero || ''), orig(r.origen)])); break;
    case 'nom': body = (x.fecha_nomina_desde ? kv([['En nómina desde', fdl(x.fecha_nomina_desde)], ['En nómina hasta', x.situacion_nomina === 1 ? 'Vigente' : fdl(x.fecha_nomina_hasta)], ['Situación', nomBadge(x.situacion_nomina)], ['Tiempo solicitud → nómina', x.fecha_solicitud ? fmt((new Date(x.fecha_nomina_desde) - new Date(x.fecha_solicitud)) / 864e5) + ' días' : '—']]) : `<div class="empty">No ha ingresado a nómina.</div>`) + `<h3 style="font:700 14px var(--f-disp);margin:18px 0 8px">Movimientos</h3>` + tbl(['Fecha', 'Movimiento', 'Motivo', 'Origen'], d.nomina.map(m => [fd(m.fecha), m.tipo === 'ALTA' ? 'Alta' : 'Baja', esc(m.motivo || ''), orig(m.origen)])); break;
    case 'apo': body = d.apoderado ? kv([['Tipo de identificación', esc(d.apoderado.tipo_id || '—')], ['No. identificación', `<span class="mask">${esc(d.apoderado.numero_id || '—')}</span>`], ['Nombre', esc(d.apoderado.nombre || '—')]]) : `<div class="empty">Sin apoderado registrado.</div>`; break;
    case 'obs': body = tbl(['Fecha', 'Observación', 'Usuario', 'Origen'], d.observaciones.map(o => [fd(o.fecha), esc(o.texto), esc(o.usuario || ''), orig(o.origen)])); break;
    case 'est': body = `<div style="margin-bottom:14px">${badge(x.estado_orden)} <span class="note">desde ${fd(x.fecha_estado)}</span></div>` + tbl(['Fecha', 'Estado', 'Motivo', 'Usuario', 'Vigente'], d.estados.map(s => [fd(s.fecha), esc(s.estado), esc(s.motivo || ''), esc(s.usuario || (s.origen === 'MATRIZ' ? 'Importación' : '')), s.vigente ? 'Sí' : ''])); break;
    case 'his': body = timeline(d); break;
    case 'ges': body = `<div class="acc-grid">${[
      ['observacion.registrar', 'obs', 'Registrar observación', 'Nota de seguimiento con fecha', 'info'],
      ['visita.registrar', 'vis', 'Registrar visita', 'Positiva, negativa o no localizado', 'pin'],
      ['resolucion.emitir', 'res', 'Emitir resolución', 'Según el catálogo institucional', 'stamp'],
      ['estado.cambiar', 'est', 'Cambiar estado', 'Con motivo obligatorio', 'pulse'],
      ['nomina.gestionar', 'nom', 'Movimiento de nómina', 'Alta o baja', 'wallet'],
      ['beneficiarios.editar', 'con', 'Actualizar contacto', 'Teléfono y dirección', 'users']].filter(a => PUEDE(a[0])).map(a => `<button class="acc" data-acc="${a[1]}">${ic(a[4], 18)}<span><b>${a[2]}</b><small>${a[3]}</small></span></button>`).join('')}</div>
      <p class="note" style="margin-top:14px">Cada registro queda en el historial del expediente y en la bitácora de auditoría con su usuario, fecha e IP.</p>`; break;
  }
  const w = document.createElement('div');
  w.innerHTML = `<div class="scrim"></div><aside class="drawer" role="dialog" aria-modal="true" aria-label="Expediente 360">
  <div class="dh"><button class="ibtn x" id="xExp" aria-label="Cerrar">${ic('close')}</button><div class="eyebrow" style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--blue7);font-weight:700">Expediente 360°</div><h2>${esc(p.nombre)}</h2>
  <div class="idrow"><span>DPI <b class="mask">${esc(dpiTxt)}</b> ${d.puede_ver_dpi && !EXP.dpi ? `<button class="link" id="showDpi">Mostrar</button>` : ''}</span><span>Exp. <b>${esc(x.numero || 's/n')}</b></span><span>${esc(x.departamento)} / <b>${esc(x.municipio)}</b></span><span><b>${p.edad ?? '—'}</b> años</span></div>
  <div style="display:flex;gap:6px;flex-wrap:wrap">${badge(x.estado_orden)}${nomBadge(x.situacion_nomina)}${x.fecha_suspension ? '<span class="badge" style="color:var(--warn);background:var(--warn-bg)">Suspensión registrada</span>' : ''}${x.fecha_fallecimiento ? '<span class="badge" style="color:var(--dead);background:color-mix(in srgb,var(--dead) 12%,transparent)">Fallecimiento registrado</span>' : ''}</div>
  <div class="tabs" role="tablist">${T.map(([k, l]) => `<button role="tab" data-tab="${k}" class="${EXP.tab === k ? 'on' : ''}" aria-selected="${EXP.tab === k}">${l}</button>`).join('')}</div></div>
  <div class="db">${body}</div></aside>`;
  document.body.append(...w.children);
  document.querySelector('.scrim').onclick = closeExp; $('#xExp').onclick = closeExp;
  document.querySelectorAll('[data-tab]').forEach(b => b.onclick = () => { EXP.tab = b.dataset.tab; drawExp(); });
  document.querySelectorAll('[data-otro]').forEach(b => b.onclick = () => openExp(+b.dataset.otro));
  document.querySelectorAll('[data-caso]').forEach(b => b.onclick = () => formCaso(b.dataset.caso));
  document.querySelectorAll('[data-acc]').forEach(b => b.onclick = () => FORMS[b.dataset.acc]());
  const sd = $('#showDpi'); if (sd) sd.onclick = async () => { try { EXP.dpi = (await api.get(`/expedientes/${EXP.id}/dpi`)).dpi; drawExp(); toast('Consulta de DPI registrada en auditoría'); } catch (e) { toast(e.message); } };
}
async function guardarYRecargar(ruta, cuerpo, msj) {
  await api.post(`/expedientes/${EXP.id}${ruta}`, cuerpo);
  cerrarModal(); toast(msj); UI.datosDesactualizados = true; await openExp(EXP.id);
}
const FORMS = {
  obs: () => modal('Registrar observación', `${campo('fecha', 'Fecha', 'date', `value="${hoyISO()}" max="${hoyISO()}"`)}${campo('texto', 'Observación', 'textarea')}`,
    { alGuardar: d => guardarYRecargar('/observaciones', { fecha: d.fecha || null, texto: d.texto }, 'Observación registrada') }),
  vis: () => modal('Registrar visita', `<div class="form-grid">${campo('fecha', 'Fecha de la visita', 'date', `value="${hoyISO()}" max="${hoyISO()}"`)}${selector('resultado', 'Resultado', [['POSITIVA', 'Positiva'], ['NEGATIVA', 'Negativa'], ['NO_LOCALIZADO', 'No localizado']])}${campo('tecnico', 'Técnico', 'text', `full value="${esc(api.usuario().nombre)}"`)}${campo('observacion', 'Observación', 'textarea', 'full')}</div>`,
    { alGuardar: d => guardarYRecargar('/visitas', { fecha: d.fecha, resultado: d.resultado, tecnico: d.tecnico || null, observacion: d.observacion || null }, 'Visita registrada') }),
  res: () => modal('Emitir resolución', `<div class="form-grid">${campo('fecha', 'Fecha', 'date', `value="${hoyISO()}" max="${hoyISO()}"`)}${campo('numero', 'Número de resolución', 'text', 'maxlength="60"')}${selector('tipo_codigo', 'Tipo de resolución', CAT.tipos_resolucion.filter(t => t.activo).map(t => [t.codigo, `${t.codigo} · ${t.nombre}`]), 'full')}${campo('descripcion', 'Descripción', 'textarea', 'full')}</div>`,
    { ancho: 680, alGuardar: d => guardarYRecargar('/resoluciones', { fecha: d.fecha, tipo_codigo: d.tipo_codigo, numero: d.numero || null, descripcion: d.descripcion || null }, 'Resolución registrada') }),
  est: () => modal('Cambiar estado', `<div class="form-grid">${selector('estado_codigo', 'Nuevo estado', CAT.estados.filter(e => e.codigo !== 'EN_NOMINA_ACTIVA').map(e => [e.codigo, e.nombre]))}${campo('fecha', 'Fecha', 'date', `value="${hoyISO()}" max="${hoyISO()}"`)}${campo('motivo', 'Motivo', 'textarea', 'full')}</div><p class="note">Si el expediente está en nómina activa, se registra automáticamente la baja.</p>`,
    { alGuardar: d => guardarYRecargar('/estado', d, 'Estado actualizado') }),
  nom: () => modal('Movimiento de nómina', `<div class="form-grid">${selector('tipo', 'Movimiento', EXP.d.expediente.situacion_nomina === 1 ? [['BAJA', 'Baja de nómina']] : [['ALTA', 'Alta en nómina']])}${campo('fecha', 'Fecha', 'date', `value="${hoyISO()}" max="${hoyISO()}"`)}${EXP.d.expediente.situacion_nomina === 1 ? selector('estado_codigo', 'Estado resultante', CAT.estados.filter(e => e.codigo !== 'EN_NOMINA_ACTIVA').map(e => [e.codigo, e.nombre]), 'full') : ''}${campo('motivo', 'Motivo / resolución que lo respalda', 'textarea', 'full')}</div>`,
    { alGuardar: d => guardarYRecargar('/nomina', { tipo: d.tipo, fecha: d.fecha, motivo: d.motivo || null, estado_codigo: d.estado_codigo || null }, 'Movimiento de nómina registrado') }),
  con: () => { const w = modal('Actualizar contacto', `${campo('telefono', 'Teléfono', 'tel')}${campo('direccion', 'Dirección', 'text', 'maxlength="400"')}`,
    { alGuardar: async d => { await api.patch(`/expedientes/${EXP.id}/contacto`, d); cerrarModal(); toast('Contacto actualizado'); await openExp(EXP.id); } });
    w.querySelector('#m_telefono').value = EXP.d.persona.telefono || ''; w.querySelector('#m_direccion').value = EXP.d.persona.direccion || ''; }
};
function formCaso(regla) {
  const r = RULES.find(z => z.k === regla);
  modal('Revisión: ' + (r ? r.t : regla), `${selector('estado', 'Resultado de la revisión', [['EN_REVISION', 'En revisión'], ['CONFIRMADO', 'Confirmado: requiere acción'], ['CORREGIDO', 'Corregido'], ['DESCARTADO', 'Descartado: no es un problema']])}${campo('comentario', 'Comentario', 'textarea')}`,
    { alGuardar: d => guardarYRecargar('/casos', { regla, estado: d.estado, comentario: d.comentario || null }, 'Revisión guardada') });
}

/* ---------- Calidad ---------- */
let CAL_EXTRA = null;
function viewCalidad() {
  const T = AG.T.c || 1, a = AG.al;
  hook(() => {
    document.querySelectorAll('[data-rule]').forEach(b => b.onclick = () => { UI.bRule = b.dataset.rule; go('beneficiarios'); });
    if (!CAL_EXTRA) api.get('/catalogos/calidad').then(x => { CAL_EXTRA = x; if (UI.view === 'calidad') renderView(); }).catch(() => {});
  });
  const dims = [['Identidad', 'CUI válido de 13 dígitos', T - a.qd, 'var(--blue5)'], ['Unicidad', 'Registros sin DPI repetido', T - a.qu, 'var(--blue5)'], ['Coherencia temporal', 'Sin fechas inconsistentes', T - a.qf, 'var(--ok)'], ['Completitud de contacto', 'Con teléfono registrado', T - a.qm, 'var(--warnf)']];
  const rows = [['qd', 'Documento sin CUI válido (cédula antigua o dígito verificador incorrecto)', a.qd, 'Identidad'], ['qu', 'DPI registrado en más de un expediente', a.qu, 'Unicidad'], ['qx', 'Número de expediente duplicado', a.qx, 'Unicidad'],
    [null, 'Personas posiblemente duplicadas (mismo nombre y fecha de nacimiento, distinto documento)', CAL_EXTRA ? CAL_EXTRA.personas_posible_duplicado : null, 'Unicidad'],
    ['qf', 'Fechas inconsistentes', a.qf, 'Coherencia'], ['qa', 'Fallecido y activo en nómina', a.qa, 'Coherencia'], ['qs', 'Suspendido y activo en nómina', a.qs, 'Coherencia'], ['qe', 'Edad imposible o fuera de rango', a.qe, 'Validez'], ['qm', 'Sin teléfono', a.qm, 'Completitud']];
  const imp = CAL_EXTRA && CAL_EXTRA.ultima_importacion;
  return header('Control', 'Calidad de datos', 'Los registros con problemas no se eliminan: se marcan y se revisan desde el Expediente 360°.') +
    `<div class="qgrid">${dims.map(([t, d, v, c]) => `<div class="card qd"><div class="note" style="font-weight:700;text-transform:uppercase;letter-spacing:.08em">${t}</div><div class="qv">${pc(v, T)}%</div><div class="meter"><i style="width:${v / T * 100}%;background:${c}"></i></div><div class="note">${d} · ${fmt(v)} de ${fmt(T)}</div></div>`).join('')}</div>
  <div class="card"><div class="ch"><div><h2>Hallazgos para revisión</h2><p>${scopeName()} · sobre ${fmt(T)} registros</p></div></div><div class="tw"><table><thead><tr><th>Regla</th><th>Dimensión</th><th class="n">Registros</th><th class="n">%</th><th></th></tr></thead><tbody>${rows.map(([k, l, n, dm]) => `<tr><td class="wrap">${l}</td><td>${dm}</td><td class="n"><b>${n == null ? '…' : fmt(n)}</b></td><td class="n">${n == null ? '' : pc(n, T, 2) + '%'}</td><td>${k ? `<button class="btn" data-rule="${k}">Revisar casos</button>` : '<span class="note">Nacional</span>'}</td></tr>`).join('')}</tbody></table></div>
  ${imp ? `<p class="note" style="margin:12px 0 0">Última importación: <b>${esc(imp.archivo)}</b> (${fts(imp.finalizado_en)}) · advertencias: ${Object.entries(imp.reporte.advertencias || {}).map(([k, v]) => `${k.replace(/_/g, ' ').toLowerCase()} ${fmt(v)}`).join(' · ') || 'ninguna'}.</p>` : ''}</div>`;
}

/* ---------- Reportes ---------- */
let REPS = null;
function viewReportes() {
  hook(async () => {
    if (!REPS) { try { REPS = await api.get('/reportes'); } catch (e) { REPS = []; } if (UI.view === 'reportes') renderView(); return; }
    document.querySelectorAll('[data-rep]').forEach(b => b.onclick = () => { UI.rep = b.dataset.rep; renderView(); });
    const g = $('#genRep'); if (!g) return;
    g.onclick = async () => {
      const m = $('#motivo').value.trim(); const er = $('#repErr'); er.textContent = '';
      if (m.length < 5) { er.textContent = 'Escriba el motivo de la exportación (mínimo 5 caracteres).'; return; }
      g.disabled = true; g.textContent = 'Generando…';
      try { await api.descargar('/reportes/exportar', { tipo: UI.rep, formato: $('#rfmt').value, motivo: m, filtros: filtrosApi() }); toast('Reporte generado · la exportación quedó registrada en la bitácora'); }
      catch (e) { er.textContent = e.message; }
      g.disabled = false; g.innerHTML = `${ic('download', 16)} Generar y descargar`;
    };
  });
  if (!REPS) return header('Control', 'Reportes', '') + '<div class="card"><div class="empty-state">Cargando catálogo…</div></div>';
  if (!REPS.find(r => r.id === UI.rep)) UI.rep = REPS[0] && REPS[0].id;
  const r = REPS.find(x => x.id === UI.rep) || {};
  return header('Control', 'Reportes', 'Los reportes respetan los filtros activos del tablero y su ámbito territorial. Toda exportación requiere motivo y queda en la bitácora.') +
    `<div class="grid g2"><div class="card"><div class="ch"><div><h2>Catálogo</h2><p>${REPS.length} reportes</p></div></div><div class="grid g3" style="gap:10px">${REPS.map(x => `<button class="rep ${UI.rep === x.id ? 'on' : ''}" data-rep="${x.id}" ${x.permitido ? '' : 'disabled title="Su rol no puede exportar datos personales"'}><b>${esc(x.titulo)}</b><small>${esc(x.descripcion)}</small>${x.datos_personales ? '<small style="color:var(--warn)">Contiene datos personales</small>' : ''}</button>`).join('')}</div></div>
    <div class="card"><div class="ch"><div><h2>${esc(r.titulo || '')}</h2><p>Parámetros</p></div></div><div style="display:flex;flex-direction:column;gap:12px">
    <div class="field">Filtros aplicados<div class="parsed" style="display:flex;flex-wrap:wrap;gap:6px">${Object.entries(F).filter(([k, v]) => v != null).map(([k, v]) => `<span class="chip">${esc(flabel(k, v))}</span>`).join('') || '<span class="note">Ninguno (todo su ámbito)</span>'}</div></div>
    <div class="field"><label for="rfmt">Formato</label><select id="rfmt"><option value="xlsx">Excel (.xlsx)</option><option value="csv">CSV</option></select></div>
    <div class="field"><label for="motivo">Motivo de la exportación</label><input id="motivo" placeholder="Ej. Informe mensual a Despacho Superior" maxlength="300"></div>
    <div class="err" id="repErr"></div>
    <button class="btn pri" id="genRep" ${r.permitido ? '' : 'disabled'}>${ic('download', 16)} Generar y descargar</button></div></div></div>`;
}

/* ---------- Importaciones ---------- */
let IMP = { lista: null, timer: 0 };
function viewImportaciones() {
  clearTimeout(IMP.timer);
  hook(() => {
    const drop = $('#drop'), inp = $('#archivo');
    if (drop) {
      drop.onclick = () => inp.click();
      drop.ondragover = e => { e.preventDefault(); drop.classList.add('over'); };
      drop.ondragleave = () => drop.classList.remove('over');
      drop.ondrop = e => { e.preventDefault(); drop.classList.remove('over'); if (e.dataTransfer.files[0]) subirArchivo(e.dataTransfer.files[0]); };
      inp.onchange = () => { if (inp.files[0]) subirArchivo(inp.files[0]); };
    }
    document.querySelectorAll('[data-modo]').forEach(b => b.onclick = () => { UI.modoImp = b.dataset.modo; renderView(); });
    document.querySelectorAll('[data-lote]').forEach(b => b.onclick = () => verLote(+b.dataset.lote));
    document.querySelectorAll('[data-err]').forEach(b => b.onclick = () => api.descargar(`/importaciones/${b.dataset.err}/errores.csv`).catch(e => toast(e.message)));
    refrescarImportaciones();
  });
  const L = IMP.lista || [];
  const est = s => ({ COMPLETADO: 'ok', VALIDADO: 'ok', ERROR: 'err', CANCELADO: 'err', PENDIENTE: 'pend' }[s] || 'run');
  return header('Control', 'Importaciones', 'Cargue la matriz completa en Excel (.xlsx) o CSV. El sistema valida la estructura, detecta errores y duplicados, y actualiza los expedientes sin duplicarlos. Puede importar la matriz las veces que necesite: cada carga actualiza estados y conserva el historial.') +
    `<div class="card"><div class="ch"><div><h2>Nueva importación</h2><p>Hasta 600 MB · columnas reconocidas por nombre (DEPARTAMENTO, MUNICIPIO, NO. EXPEDIENTE, NOMBRE, DPI, … ESTADO ACTUAL)</p></div>
    <div class="seg" role="group" aria-label="Modo"><button data-modo="INCREMENTAL" class="${UI.modoImp !== 'VALIDAR' ? 'on' : ''}">Importar</button><button data-modo="VALIDAR" class="${UI.modoImp === 'VALIDAR' ? 'on' : ''}">Solo validar</button></div></div>
    <div class="drop" id="drop" tabindex="0" role="button"><b>Arrastre aquí el archivo o haga clic para elegirlo</b><span class="note">${UI.modoImp === 'VALIDAR' ? 'Modo validación: revisa el archivo y genera el reporte sin modificar la base.' : 'Modo importación: inserta expedientes nuevos y actualiza los existentes.'}</span><div id="subida" style="margin-top:10px"></div></div>
    <input type="file" id="archivo" accept=".xlsx,.xlsm,.csv,.txt" hidden></div>
    <div class="card"><div class="ch"><div><h2>Historial de cargas</h2><p>Se actualiza automáticamente</p></div></div>
    ${L.length ? `<div class="tw"><table><thead><tr><th>Lote</th><th>Archivo</th><th>Estado</th><th>Avance</th><th class="n">Filas</th><th class="n">Nuevas</th><th class="n">Actualizadas</th><th class="n">Rechazadas</th><th class="n">Advertencias</th><th>Usuario</th><th>Fecha</th><th></th></tr></thead><tbody>${L.map(l => `<tr><td>#${l.id}</td><td class="wrap">${esc(l.archivo)}<br><span class="note">${esc(l.modo === 'VALIDAR' ? 'Solo validación' : 'Importación')}${l.codificacion ? ' · ' + esc(l.codificacion) : ''}</span></td><td><span class="st ${est(l.estado)}">${l.estado}</span></td><td><div class="bar"><i style="width:${l.progreso}%"></i></div><span class="note">${esc(l.mensaje || '')}</span></td><td class="n">${fmt(l.total_filas)}</td><td class="n">${fmt(l.filas_insertadas)}</td><td class="n">${fmt(l.filas_actualizadas)}</td><td class="n">${fmt(l.filas_rechazadas)}</td><td class="n">${fmt(l.advertencias)}</td><td>${esc(l.usuario || 'consola')}</td><td class="tnum">${fts(l.creado_en)}</td><td style="display:flex;gap:6px"><button class="btn" data-lote="${l.id}">Detalle</button>${l.advertencias || l.filas_rechazadas ? `<button class="btn" data-err="${l.id}">${ic('download', 14)} Errores</button>` : ''}</td></tr>`).join('')}</tbody></table></div>` : '<div class="empty-state">Aún no hay importaciones.</div>'}</div>`;
}
async function refrescarImportaciones() {
  try {
    const l = await api.get('/importaciones');
    const cambio = JSON.stringify(l) !== JSON.stringify(IMP.lista);
    const antes = IMP.lista; IMP.lista = l;
    if (cambio && UI.view === 'importaciones' && !document.querySelector('.modal-w')) renderView();
    const activos = l.some(x => !['COMPLETADO', 'VALIDADO', 'ERROR', 'CANCELADO'].includes(x.estado));
    if (antes && antes.some(x => !['COMPLETADO', 'ERROR', 'CANCELADO', 'VALIDADO'].includes(x.estado)) && l.some(x => x.estado === 'COMPLETADO' && antes.find(a => a.id === x.id && a.estado !== 'COMPLETADO'))) {
      toast('Importación completada · actualizando el tablero'); await cargarDatos();
    }
    if (activos && UI.view === 'importaciones') IMP.timer = setTimeout(refrescarImportaciones, 3000);
  } catch (e) { }
}
function subirArchivo(file) {
  const box = $('#subida');
  const fdt = new FormData(); fdt.append('archivo', file); fdt.append('modo', UI.modoImp === 'VALIDAR' ? 'VALIDAR' : 'INCREMENTAL');
  box.innerHTML = `<div class="bar" style="max-width:360px;margin:0 auto"><i style="width:0%"></i></div><span class="note">Subiendo ${esc(file.name)} (${(file.size / 1048576).toFixed(1)} MB)…</span>`;
  // XMLHttpRequest para mostrar el avance de la subida
  const x = new XMLHttpRequest(); x.open('POST', '/api/v1/importaciones'); x.withCredentials = true;
  api._token && x.setRequestHeader('Authorization', 'Bearer ' + api._token());
  x.upload.onprogress = e => { if (e.lengthComputable) box.querySelector('i').style.width = (e.loaded / e.total * 100) + '%'; };
  x.onload = () => {
    if (x.status === 201) { toast('Archivo recibido · en cola de procesamiento'); IMP.lista = null; renderView(); }
    else { let m = 'No se pudo subir el archivo'; try { m = JSON.parse(x.responseText).detail || m; } catch (e) {} box.innerHTML = `<span class="err">${esc(m)}</span>`; }
  };
  x.onerror = () => { box.innerHTML = '<span class="err">Error de red al subir el archivo</span>'; };
  x.send(fdt);
}
async function verLote(id) {
  const l = await api.get('/importaciones/' + id);
  const rep = l.reporte || {};
  const dif = rep.diferencias || [];
  modal(`Lote #${l.id} · ${esc(l.archivo)}`, `<div class="kvs"><div><small>Estado</small><b>${l.estado}</b></div><div><small>Filas leídas</small><b>${fmt(l.total_filas)}</b></div><div><small>Nuevas</small><b>${fmt(l.filas_insertadas)}</b></div><div><small>Actualizadas</small><b>${fmt(l.filas_actualizadas)}</b></div><div><small>Rechazadas</small><b>${fmt(l.filas_rechazadas)}</b></div><div><small>Advertencias</small><b>${fmt(l.advertencias)}</b></div></div>
    <p class="note">${esc(l.mensaje || '')}</p>
    <h3 style="font:700 14px var(--f-disp);margin:6px 0 0">Conciliación matriz vs. base</h3><p class="note">${rep.total_matriz != null ? `Matriz: <b>${fmt(rep.total_matriz)}</b> filas · en base para este lote: <b>${fmt(rep.total_base_lote)}</b> · ${dif.length ? `<b style="color:var(--crit)">${dif.length} diferencias por departamento × estado</b>` : '<b style="color:var(--ok)">sin diferencias por departamento × estado</b>'}` : 'Sin reporte.'}</p>
    ${l.resumen_errores.length ? `<div class="tw"><table><thead><tr><th>Nivel</th><th>Regla</th><th class="n">Filas</th><th class="n">Primera línea</th></tr></thead><tbody>${l.resumen_errores.map(e => `<tr><td>${e.nivel}</td><td>${esc(e.regla)}</td><td class="n">${fmt(e.n)}</td><td class="n">${e.primera_linea ?? ''}</td></tr>`).join('')}</tbody></table></div>` : ''}`, { ancho: 760 });
}

/* ---------- Administración ---------- */
let ADM = { tab: 'usuarios', usuarios: null, roles: null, bit: null, par: null, filtro: {} };
function viewAdmin() {
  const tabs = [['usuarios', 'Usuarios', 'usuarios.gestionar'], ['roles', 'Roles y permisos', 'roles.gestionar'], ['bitacora', 'Bitácora de auditoría', 'auditoria.ver'], ['parametros', 'Parámetros y catálogos', 'catalogos.gestionar']].filter(t => PUEDE(t[2]));
  if (!tabs.find(t => t[0] === ADM.tab)) ADM.tab = tabs[0] && tabs[0][0];
  hook(() => {
    document.querySelectorAll('[data-atab]').forEach(b => b.onclick = () => { ADM.tab = b.dataset.atab; renderView(); });
    cargarAdmin();
  });
  return header('Control', 'Administración', 'Usuarios, roles y permisos configurables, parámetros y bitácora de auditoría.') +
    `<div class="card"><div class="subtabs">${tabs.map(([k, l]) => `<button data-atab="${k}" class="${ADM.tab === k ? 'on' : ''}">${l}</button>`).join('')}</div><div id="admBody"><div class="empty-state">Cargando…</div></div></div>`;
}
async function cargarAdmin() {
  const box = $('#admBody'); if (!box) return;
  try {
    if (ADM.tab === 'usuarios') {
      const [us, rr] = await Promise.all([api.get('/admin/usuarios'), api.get('/admin/roles')]); ADM.roles = rr;
      box.innerHTML = `<div style="display:flex;justify-content:flex-end;margin:10px 0"><button class="btn pri" id="nuevoU">${ic('users', 16)} Nuevo usuario</button></div>
      <div class="tw"><table><thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Ámbito</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead><tbody>${us.map(u => `<tr><td><b>${esc(u.usuario)}</b></td><td>${esc(u.nombre)}<br><span class="note">${esc(u.correo || '')}</span></td><td>${esc(u.rol)}</td><td>${esc(u.municipio || u.departamento || 'Nacional')}</td><td class="tnum">${fts(u.ultimo_acceso)}</td><td>${u.activo ? (u.bloqueado_hasta && new Date(u.bloqueado_hasta) > new Date() ? '<span class="st pend">Bloqueado</span>' : '<span class="st ok">Activo</span>') : '<span class="st err">Inactivo</span>'}${u.debe_cambiar_clave ? '<br><span class="note">Debe cambiar clave</span>' : ''}</td><td style="display:flex;gap:6px"><button class="btn" data-eu="${u.id}">Editar</button><button class="btn" data-rc="${u.id}">Restablecer clave</button></td></tr>`).join('')}</tbody></table></div>`;
      $('#nuevoU').onclick = () => formUsuario(null);
      box.querySelectorAll('[data-eu]').forEach(b => b.onclick = () => formUsuario(us.find(u => u.id === +b.dataset.eu)));
      box.querySelectorAll('[data-rc]').forEach(b => b.onclick = async () => { const r = await api.post(`/admin/usuarios/${b.dataset.rc}/restablecer-clave`); mostrarClave(r.clave_temporal, 'Clave restablecida'); });
    } else if (ADM.tab === 'roles') {
      const rr = await api.get('/admin/roles'); ADM.roles = rr;
      const mods = [...new Set(rr.permisos.map(p => p.modulo))];
      box.innerHTML = `<p class="note" style="margin:12px 0">Marque los permisos de cada rol y guarde. Los cambios aplican de inmediato y quedan en la bitácora.</p>
      <div class="tw"><table class="perm"><thead><tr><th>Permiso</th>${rr.roles.map(r => `<th title="${esc(r.descripcion || '')}">${esc(r.nombre)}<br><span class="note">${r.usuarios} usuario(s)</span></th>`).join('')}</tr></thead><tbody>${mods.map(m => `<tr><td colspan="${rr.roles.length + 1}" style="font-weight:700;color:var(--blue7);background:var(--glass-2)">${m}</td></tr>` + rr.permisos.filter(p => p.modulo === m).map(p => `<tr><td>${esc(p.descripcion)}<br><span class="note">${p.codigo}</span></td>${rr.roles.map(r => `<td><input type="checkbox" data-rol="${r.id}" data-perm="${p.codigo}" ${r.permisos.includes(p.codigo) ? 'checked' : ''} aria-label="${esc(p.descripcion)} · ${esc(r.nombre)}"></td>`).join('')}</tr>`).join('')).join('')}</tbody></table></div>
      <div style="display:flex;justify-content:space-between;gap:8px;margin-top:12px"><button class="btn" id="nuevoRol">Nuevo rol</button><button class="btn pri" id="guardarPerm">Guardar permisos</button></div>`;
      $('#guardarPerm').onclick = async () => {
        try { for (const r of rr.roles) { const sel = [...box.querySelectorAll(`[data-rol="${r.id}"]:checked`)].map(i => i.dataset.perm); if (JSON.stringify(sel.sort()) !== JSON.stringify([...r.permisos].sort())) await api.put(`/admin/roles/${r.id}/permisos`, sel); } toast('Permisos guardados'); cargarAdmin(); }
        catch (e) { toast(e.message); }
      };
      $('#nuevoRol').onclick = () => modal('Nuevo rol', `${campo('codigo', 'Código (MAYÚSCULAS, sin espacios)', 'text', 'pattern="[A-Z_]{3,30}"')}${campo('nombre', 'Nombre')}${campo('descripcion', 'Descripción', 'textarea')}`, { alGuardar: async d => { await api.post('/admin/roles', d); cerrarModal(); toast('Rol creado'); cargarAdmin(); } });
    } else if (ADM.tab === 'bitacora') {
      const f = ADM.filtro;
      const b = await api.get('/admin/bitacora', { ...f, limite: 100 });
      box.innerHTML = `<div class="form-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));margin:12px 0"><div class="field"><label for="bu">Usuario</label><input id="bu" value="${esc(f.usuario || '')}"></div><div class="field"><label for="ba">Acción</label><select id="ba"><option value="">Todas</option>${['LOGIN', 'LOGIN_FALLIDO', 'VER_EXPEDIENTE', 'VER_DPI', 'EXPORTAR', 'IMPORTAR', 'SUBIR_IMPORTACION', 'INSERT', 'UPDATE', 'DELETE', 'CAMBIO_CLAVE', 'RESTABLECER_CLAVE'].map(a => `<option ${f.accion === a ? 'selected' : ''}>${a}</option>`).join('')}</select></div><div class="field"><label for="bd">Desde</label><input id="bd" type="date" value="${f.desde || ''}"></div><div class="field"><label for="bh">Hasta</label><input id="bh" type="date" value="${f.hasta || ''}"></div></div>
      <div style="display:flex;justify-content:flex-end;margin-bottom:10px"><button class="btn pri" id="bFil">Filtrar</button></div>
      <div class="tw"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Registro</th><th>Antes</th><th>Después</th><th>IP</th></tr></thead><tbody>${b.map(x => `<tr><td class="tnum">${fts(x.ts)}</td><td>${esc(x.usuario || '—')}</td><td><b>${esc(x.accion)}</b><br><span class="note">${esc(x.modulo || '')}</span></td><td>${esc(x.entidad || '')} ${esc(x.entidad_id || '')}</td><td><div class="json">${x.antes ? esc(JSON.stringify(x.antes, null, 1)) : ''}</div></td><td><div class="json">${x.despues ? esc(JSON.stringify(x.despues, null, 1)) : ''}</div></td><td>${esc(x.ip || '')}</td></tr>`).join('')}</tbody></table></div>`;
      $('#bFil').onclick = () => { ADM.filtro = { usuario: $('#bu').value || undefined, accion: $('#ba').value || undefined, desde: $('#bd').value || undefined, hasta: $('#bh').value || undefined }; cargarAdmin(); };
    } else if (ADM.tab === 'parametros') {
      const ps = await api.get('/admin/parametros');
      box.innerHTML = `<div class="tw" style="margin-top:12px"><table><thead><tr><th>Parámetro</th><th>Valor</th><th>Descripción</th><th></th></tr></thead><tbody>${ps.map(p => `<tr><td><b>${esc(p.clave)}</b></td><td><div class="json">${esc(JSON.stringify(p.valor))}</div></td><td class="wrap">${esc(p.descripcion || '')}</td><td><button class="btn" data-par="${esc(p.clave)}">Editar</button></td></tr>`).join('')}</tbody></table></div>
      <h3 style="font:700 14px var(--f-disp);margin:20px 0 8px">Catálogo de tipos de resolución</h3>
      <div class="tw"><table><thead><tr><th>Código</th><th>Nombre</th><th>Familia</th><th>Activo</th><th></th></tr></thead><tbody>${CAT.tipos_resolucion.map(t => `<tr><td><b>${esc(t.codigo)}</b></td><td class="wrap">${esc(t.nombre)}</td><td>${esc((CAT.familias_resolucion.find(f => f.id === t.familia_id) || {}).nombre || '')}</td><td>${t.activo ? 'Sí' : 'No'}</td><td><button class="btn" data-tr="${esc(t.codigo)}">Editar</button></td></tr>`).join('')}</tbody></table></div>`;
      box.querySelectorAll('[data-par]').forEach(b => b.onclick = () => { const p = ps.find(x => x.clave === b.dataset.par);
        const w = modal('Parámetro ' + p.clave, `<p class="note">${esc(p.descripcion || '')}</p>${campo('valor', 'Valor (JSON)', 'textarea')}`, { alGuardar: async d => { let v; try { v = JSON.parse(d.valor); } catch (e) { throw new Error('El valor no es JSON válido'); } await api.put('/admin/parametros/' + encodeURIComponent(p.clave), v); cerrarModal(); toast('Parámetro guardado'); cargarAdmin(); } });
        w.querySelector('#m_valor').value = JSON.stringify(p.valor, null, 2); });
      box.querySelectorAll('[data-tr]').forEach(b => b.onclick = () => { const t = CAT.tipos_resolucion.find(x => x.codigo === b.dataset.tr);
        const w = modal('Tipo de resolución ' + t.codigo, `${campo('nombre', 'Nombre')}${selector('familia_id', 'Familia', CAT.familias_resolucion.map(f => [f.id, f.nombre]))}${selector('activo', 'Activo', [['true', 'Sí'], ['false', 'No']])}`, { alGuardar: async d => { await api.put('/admin/tipos-resolucion', { codigo: t.codigo, nombre: d.nombre, familia_id: +d.familia_id, activo: d.activo === 'true' }); CAT = await api.get('/catalogos'); cerrarModal(); toast('Catálogo actualizado · el tablero se recalcula en el próximo refresco'); cargarAdmin(); } });
        w.querySelector('#m_nombre').value = t.nombre; w.querySelector('#m_familia_id').value = t.familia_id; w.querySelector('#m_activo').value = String(t.activo); });
    }
  } catch (e) { box.innerHTML = `<div class="empty-state">${esc(e.message)}</div>`; }
}
function formUsuario(u) {
  const roles = (ADM.roles && ADM.roles.roles) || [];
  const w = modal(u ? 'Editar usuario ' + u.usuario : 'Nuevo usuario', `<div class="form-grid">
    ${u ? '' : campo('usuario', 'Usuario (sin espacios)', 'text', 'required pattern="[A-Za-z0-9._-]{3,40}"')}
    ${campo('nombre', 'Nombre completo', 'text', u ? 'full' : '')}${campo('correo', 'Correo institucional', 'email', 'full')}
    ${selector('rol_id', 'Rol', roles.map(r => [r.id, r.nombre]))}
    ${selector('ambito', 'Ámbito territorial', [['', 'Nacional'], ...CAT.departamentos.map(d => ['d' + d.id, 'Departamento: ' + d.nombre]), ...CAT.municipios.map(m => ['m' + m.id, 'Municipio: ' + m.nombre])])}
    ${u ? selector('activo', 'Estado', [['true', 'Activo'], ['false', 'Inactivo']]) : ''}</div>`,
    { ancho: 680, alGuardar: async d => {
      const amb = d.ambito || '';
      const body = { nombre: d.nombre, correo: d.correo || null, rol_id: +d.rol_id };
      if (amb.startsWith('d')) { body.departamento_id = +amb.slice(1); body.municipio_id = null; }
      else if (amb.startsWith('m')) { body.municipio_id = +amb.slice(1); }
      if (u) { if (!amb) body.ambito_nacional = true; body.activo = d.activo === 'true'; if (u.id === api.usuario().id) { delete body.rol_id; delete body.activo; } await api.patch('/admin/usuarios/' + u.id, body); cerrarModal(); toast('Usuario actualizado'); cargarAdmin(); }
      else { const r = await api.post('/admin/usuarios', { ...body, usuario: d.usuario }); mostrarClave(r.clave_temporal, 'Usuario creado'); cargarAdmin(); }
    } });
  if (u) { w.querySelector('#m_nombre').value = u.nombre; w.querySelector('#m_correo').value = u.correo || ''; w.querySelector('#m_rol_id').value = u.rol_id; w.querySelector('#m_activo').value = String(u.activo); w.querySelector('#m_ambito').value = u.municipio_id ? 'm' + u.municipio_id : u.departamento_id ? 'd' + u.departamento_id : ''; }
}
function mostrarClave(c, titulo) {
  modal(titulo, `<p>Entregue esta contraseña temporal al usuario por un canal seguro. Se mostrará solo esta vez y el sistema le pedirá cambiarla al ingresar.</p><div class="clave">${esc(c)}</div>`, {});
}

/* ---------- búsqueda universal (API) ---------- */
function initSearch() {
  const q = $('#q'), sg = $('#sugg'); let act = -1, res = [], t = 0, seq = 0;
  const draw = () => {
    if (!res.length) { sg.hidden = true; return; } sg.hidden = false;
    sg.innerHTML = res.slice(0, 6).map((p, i) => `<button data-sid="${p.id}" class="${i === act ? 'act' : ''}"><b>${esc(p.nombre)}</b><span>${badge(p.estado_orden)}</span><small>Exp. ${esc(p.numero || 's/n')} · ${esc(p.dpi || '—')} · ${esc(p.municipio)}, ${esc(p.departamento)}</small></button>`).join('') + `<button data-all="1"><b style="color:var(--blue7)">Ver todos los resultados en Beneficiarios</b><small>${res.length >= 6 ? 'Hay más coincidencias' : ''}</small></button>`;
    sg.querySelectorAll('[data-sid]').forEach(b => b.onmousedown = e => { e.preventDefault(); openExp(+b.dataset.sid); sg.hidden = true; });
    sg.querySelector('[data-all]').onmousedown = e => { e.preventDefault(); verTodo(); };
  };
  const verTodo = () => { UI.bq = q.value; UI.bRule = null; sg.hidden = true; go('beneficiarios'); };
  q.oninput = () => { clearTimeout(t); const v = q.value.trim(); if (v.length < 3 || !PUEDE('beneficiarios.ver')) { res = []; draw(); return; }
    t = setTimeout(async () => { const s = ++seq; try { const d = await api.get('/expedientes', { q: v, limite: 6, orden: /[a-z]/i.test(v) ? 'relevancia' : 'fsol' }); if (s === seq) { res = d.items; act = -1; draw(); } } catch (e) { } }, 280); };
  q.onkeydown = e => { if (e.key === 'ArrowDown') { act = Math.min(act + 1, Math.min(res.length, 6) - 1); draw(); e.preventDefault(); } else if (e.key === 'ArrowUp') { act = Math.max(act - 1, 0); draw(); e.preventDefault(); } else if (e.key === 'Enter') { if (act >= 0 && res[act]) { openExp(res[act].id); sg.hidden = true; } else verTodo(); } else if (e.key === 'Escape') { sg.hidden = true; } };
  q.onblur = () => setTimeout(() => sg.hidden = true, 150);
  document.addEventListener('keydown', e => { if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { e.preventDefault(); q.focus(); } if (e.key === 'Escape') { closeExp(); cerrarModal(); } });
}

document.addEventListener('click', e => {
  const g = e.target.closest('[data-go]'); if (g) { go(g.dataset.go); return; }
  const a = e.target.closest('[data-accion="nueva-solicitud"]'); if (a) nuevaSolicitud();
});
function render() { compute(); renderNav(); if (C) renderFilters(); renderView(); }
