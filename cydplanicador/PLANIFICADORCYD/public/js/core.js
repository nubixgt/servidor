/* Núcleo de la aplicación: estado, API, enrutador, diálogos y utilidades. */
'use strict';
window.CYD = { components: {} };
// Subruta pública donde está publicada la app (vacía si está en la raíz del dominio)
CYD.base = (document.querySelector('meta[name="base-path"]') || {}).content || '';
CYD.url = u => (typeof u === 'string' && u.startsWith('/') ? CYD.base + u : u);
(function () {
  const { reactive } = Vue;

  const store = reactive({
    user: null, mailEnabled: false, institution: null, ready: false,
    route: { path: '/', params: {}, query: {}, name: '' },
    years: [], yearId: null,
    toasts: [], modal: null,
    leaveGuard: null, // función que devuelve true si hay cambios pendientes
  });

  // ---------------------------------------------------------------- API
  class ApiError extends Error {
    constructor(status, message, data) { super(message); this.status = status; this.data = data || {}; }
  }
  async function api(method, url, body, opts = {}) {
    const init = { method, headers: { 'X-Requested-With': 'cyd-app' }, credentials: 'same-origin' };
    if (body !== undefined) {
      if (body instanceof Blob) { init.body = body; init.headers['Content-Type'] = body.type; }
      else { init.body = JSON.stringify(body); init.headers['Content-Type'] = 'application/json'; }
    }
    let res;
    try { res = await fetch(CYD.url(url), init); }
    catch { throw new ApiError(0, 'No hay conexión con el servidor. Verifique su conexión a internet e intente nuevamente.'); }
    let data = null;
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) { try { data = await res.json(); } catch { data = null; } }
    if (!res.ok) {
      const msg = (data && data.error) || `Error ${res.status}`;
      if (res.status === 401 && !opts.silent401) {
        store.user = null;
        if (!PUBLIC.includes(store.route.name)) go('/ingresar');
      }
      if (res.status === 403 && data && data.code === 'MUST_CHANGE_PASSWORD') go('/cambiar-contrasena');
      throw new ApiError(res.status, msg, data);
    }
    return data;
  }

  async function loadMe() {
    const r = await api('GET', '/api/auth/me', undefined, { silent401: true });
    store.user = r.user; store.mailEnabled = r.mailEnabled; store.institution = r.institution;
    return r.user;
  }

  async function loadYears() {
    store.years = await api('GET', '/api/school-years');
    let saved = null;
    try { saved = Number(localStorage.getItem('cyd-year')); } catch { /* sin almacenamiento */ }
    if (store.years.some(y => y.id === saved)) store.yearId = saved;
    else if (store.years.length) store.yearId = (store.years.find(y => y.active) || store.years[0]).id;
    else store.yearId = null;
  }
  function setYear(id) {
    store.yearId = id;
    try { localStorage.setItem('cyd-year', String(id)); } catch { /* sin almacenamiento */ }
  }

  // ---------------------------------------------------------------- enrutador
  const ROUTES = [
    ['/ingresar', 'login'], ['/registro', 'register'], ['/recuperar', 'forgot'], ['/restablecer', 'reset'],
    ['/cambiar-contrasena', 'force-password'],
    ['/inicio', 'dashboard'], ['/asignaciones', 'assignments'], ['/perfil', 'profile'], ['/cursos', 'courses'],
    ['/plan/:id/hoja', 'sheet'], ['/plan/:id/plan-bimestral', 'plan-pdf'], ['/plan/:id', 'editor'],
    ['/admin', 'admin-plans'], ['/admin/institucion', 'admin-institution'], ['/admin/ciclos', 'admin-years'],
    ['/admin/grados', 'admin-grades'], ['/admin/cursos', 'admin-courses'], ['/admin/docentes', 'admin-teachers'], ['/admin/usuarios', 'admin-users'],
    ['/admin/bitacora', 'admin-audit'],
  ];
  const PUBLIC = ['login', 'register', 'forgot', 'reset'];

  function parseHash() {
    const h = location.hash.replace(/^#/, '') || '/';
    const [path, qs] = h.split('?');
    const query = Object.fromEntries(new URLSearchParams(qs || ''));
    for (const [pat, name] of ROUTES) {
      const keys = [];
      const re = new RegExp('^' + pat.replace(/:(\w+)/g, (_, k) => { keys.push(k); return '([^/]+)'; }) + '/?$');
      const m = path.match(re);
      if (m) return { path, name, query, params: Object.fromEntries(keys.map((k, i) => [k, decodeURIComponent(m[i + 1])])) };
    }
    return { path, name: '', query, params: {} };
  }
  function go(path) { if (location.hash !== '#' + path) location.hash = '#' + path; else route(); }
  let lastHash = location.hash;
  async function route() {
    const r = parseHash();
    if (store.leaveGuard && lastHash !== location.hash && store.leaveGuard()) {
      const ok = await confirm({ title: 'Hay cambios sin guardar', body: ['Algunos cambios aún no se han guardado en el servidor. Si sale ahora podría perderlos.'], confirmText: 'Salir de todos modos', danger: true, cancelText: 'Permanecer' });
      if (!ok) { history.replaceState(null, '', lastHash || '#/inicio'); return; }
    }
    lastHash = location.hash;
    if (!store.user && !PUBLIC.includes(r.name)) { store.route = { ...r, name: 'login' }; if (r.name !== 'login') history.replaceState(null, '', '#/ingresar'); return; }
    if (store.user && store.user.must_change_password && r.name !== 'force-password') { history.replaceState(null, '', '#/cambiar-contrasena'); store.route = { ...parseHash() }; return; }
    if (store.user && PUBLIC.includes(r.name) && r.name !== 'reset') { history.replaceState(null, '', '#' + home()); store.route = parseHash(); return; }
    if (!r.name) { history.replaceState(null, '', '#' + (store.user ? home() : '/ingresar')); store.route = parseHash(); return; }
    if (store.user && r.name.startsWith('admin') && store.user.role !== 'admin') { history.replaceState(null, '', '#/inicio'); store.route = parseHash(); return; }
    if (store.user && store.user.role === 'admin' && ['dashboard', 'assignments'].includes(r.name)) { history.replaceState(null, '', '#/admin'); store.route = parseHash(); return; }
    store.route = r;
    window.scrollTo(0, 0);
  }
  function home() { return store.user && store.user.role === 'admin' ? '/admin' : '/inicio'; }
  window.addEventListener('hashchange', route);

  // ---------------------------------------------------------------- diálogos y avisos
  function toast(text, kind = 'info', ms = 4500) {
    const t = { id: Math.random(), text, kind };
    store.toasts.push(t);
    setTimeout(() => { const i = store.toasts.indexOf(t); if (i >= 0) store.toasts.splice(i, 1); }, ms);
  }
  function confirm(opts) {
    return new Promise(resolve => {
      store.modal = { kind: 'confirm', confirmText: 'Confirmar', cancelText: 'Cancelar', ...opts, resolve };
    });
  }
  function alertBox(opts) {
    return new Promise(resolve => { store.modal = { kind: 'alert', confirmText: 'Entendido', ...opts, resolve }; });
  }

  // ---------------------------------------------------------------- utilidades
  const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  function fmtDate(d) { if (!d) return '—'; const [y, m, dd] = d.split('-'); return `${dd}/${m}/${y}`; }
  function fmtDateLong(d) { if (!d) return '—'; const [y, m, dd] = d.split('-'); return `${Number(dd)} de ${MONTHS[Number(m) - 1]} de ${y}`; }
  function fmtDateTime(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('es-GT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }
  function uuid() {
    if (crypto.randomUUID) return crypto.randomUUID();
    return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
  }
  const ORD = { 1: 'Primer', 2: 'Segundo', 3: 'Tercer', 4: 'Cuarto' };
  const STATUS = {
    sin_iniciar: { text: 'Sin iniciar', cls: 'none', ic: '○' },
    borrador: { text: 'Borrador', cls: 'draft', ic: '✎' },
    listo: { text: 'Listo', cls: 'ready', ic: '✓' },
  };
  const norm = s => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
  function fieldErrors(e) { return (e && e.data && e.data.fields) || {}; }
  function initials(name) { return String(name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join(''); }

  Object.assign(CYD, { store, api, ApiError, loadMe, loadYears, setYear, go, route, toast, confirm, alert: alertBox,
    fmtDate, fmtDateLong, fmtDateTime, uuid, ORD, STATUS, fieldErrors, initials, PUBLIC, home, norm });

  // ---------------------------------------------------------------- componentes compartidos
  CYD.components['status-badge'] = {
    props: ['status'],
    computed: { s() { return STATUS[this.status] || STATUS.sin_iniciar; } },
    template: `<span class="badge" :class="s.cls"><span aria-hidden="true">{{ s.ic }}</span>{{ s.text }}</span>`,
  };

  CYD.components['field-err'] = {
    props: ['msg'],
    template: `<div v-if="msg" class="err-text" role="alert">{{ msg }}</div>`,
  };

  CYD.components['year-select'] = {
    props: { label: { default: 'Ciclo escolar' } },
    setup() { return { store, setYear }; },
    template: `
      <div class="field" style="min-width:170px">
        <label for="year-sel">{{ label }}</label>
        <select id="year-sel" :value="store.yearId" @change="setYear(Number($event.target.value))" :disabled="!store.years.length">
          <option v-if="!store.years.length" value="">Sin ciclos configurados</option>
          <option v-for="y in store.years" :key="y.id" :value="y.id">{{ y.name }}{{ y.active ? '' : ' (inactivo)' }}</option>
        </select>
      </div>`,
  };

  CYD.components['loading-box'] = {
    props: { text: { default: 'Cargando…' } },
    template: `<div class="loading" role="status"><div class="spinner"></div>{{ text }}</div>`,
  };
})();
