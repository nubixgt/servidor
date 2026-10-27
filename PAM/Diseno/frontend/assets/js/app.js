/* =====================================================================
   Arranque: sesión, carga de datos del tablero y eventos globales
   ===================================================================== */
async function cargarDatos() {
  const [d, geo] = await Promise.all([api.get('/tablero/datos'), G ? Promise.resolve(G) : fetch('assets/data/geo.json').then(r => r.json())]);
  G = geo; if (!Object.keys(BBOX).length) for (const [k, p] of Object.entries(G.paths)) BBOX[k] = bboxOf(p);
  for (const k in F) F[k] = null;
  prepCube(d.cubo); S = d.series;
  CORTE = d.meta.fecha_corte ? fd(d.meta.fecha_corte) : '';
  const refr = d.meta.refrescado_en ? new Date(d.meta.refrescado_en) : null;
  $('#liveTxt').textContent = `Datos al ${CORTE || '—'}`;
  $('#live').title = `Cifras calculadas en el servidor sobre la base del programa. Agregados actualizados: ${refr ? refr.toLocaleString('es-GT') : '—'}.`;
  render();
}

function mostrarUsuario(u) {
  const ini = u.nombre.split(/\s+/).filter(Boolean).slice(0, 2).map(s => s[0]).join('').toUpperCase();
  $('#ava').textContent = ini || '··'; $('#uNombre').textContent = u.nombre; $('#uRol').textContent = u.rol_nombre;
  $('#askBtn').hidden = !PUEDE('dashboard.ver');
}

function pantallaLogin(modo = 'login', msg = '') {
  $('#app').hidden = true; $('#login').hidden = false;
  const cambio = modo === 'cambio';
  $('#loginCampos').hidden = cambio; $('#cambioCampos').hidden = !cambio;
  $('#loginTitulo').textContent = cambio ? 'Cambie su contraseña' : 'Programa del Adulto Mayor';
  $('#loginSub').textContent = cambio ? 'Por seguridad debe definir una contraseña personal antes de continuar.' : 'Sistema Integral de Gestión y Monitoreo. Ingrese con su usuario institucional.';
  $('#loginBtn').textContent = cambio ? 'Guardar y continuar' : 'Ingresar';
  $('#loginErr').textContent = msg;
  setTimeout(() => (cambio ? $('#ca') : $('#lu')).focus(), 50);
  $('#loginForm').onsubmit = async e => {
    e.preventDefault(); $('#loginErr').textContent = ''; $('#loginBtn').disabled = true;
    try {
      if (cambio) {
        if (!$('#ca').value || !$('#cn').value) throw new Error('Complete los campos');
        if ($('#cn').value !== $('#cc').value) throw new Error('Las contraseñas nuevas no coinciden');
        await api.post('/auth/cambiar-clave', { actual: $('#ca').value, nueva: $('#cn').value });
        const u = await api.get('/auth/yo'); api.setUsuario(u); await entrar();
      } else {
        if (!$('#lu').value.trim() || !$('#lc').value) throw new Error('Escriba su usuario y contraseña');
        const d = await api.login($('#lu').value.trim(), $('#lc').value);
        $('#lc').value = '';
        if (d.usuario.debe_cambiar_clave) { $('#ca').value = ''; pantallaLogin('cambio'); }
        else await entrar();
      }
    } catch (err) { $('#loginErr').textContent = err.message; }
    $('#loginBtn').disabled = false;
  };
}

async function entrar() {
  const u = api.usuario();
  if (u.debe_cambiar_clave) return pantallaLogin('cambio');
  $('#login').hidden = true; $('#app').hidden = false;
  mostrarUsuario(u);
  try {
    CAT = await api.get('/catalogos');
    if (PUEDE('dashboard.ver')) await cargarDatos();
    else { $('#fbar').hidden = true; $('#askBtn').hidden = true; UI.view = 'beneficiarios'; renderNav(); renderView(); }
  } catch (e) { $('#view').innerHTML = `<div class="loading">No se pudieron cargar los datos: ${esc(e.message)}</div>`; }
}

function eventosGlobales() {
  $('#collapse').onclick = () => $('#side').classList.toggle('min');
  $('#menuM').onclick = () => $('#side').classList.toggle('open');
  $('#bell').onclick = () => go('alertas');
  $('#salir').onclick = async () => { await api.logout(); location.hash = ''; location.reload(); };
  $('#theme').onclick = () => { const r = document.documentElement; const dark = r.dataset.theme ? r.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches; r.dataset.theme = dark ? 'light' : 'dark'; try { localStorage.setItem('pam-tema', r.dataset.theme); } catch (e) {} };
  try { const t = localStorage.getItem('pam-tema'); if (t) document.documentElement.dataset.theme = t; } catch (e) {}
  api.alExpirar = () => pantallaLogin('login', 'Su sesión expiró. Ingrese de nuevo.');
  initSearch();
  // Refresco silencioso de los agregados cada 15 minutos si hubo cambios
  setInterval(async () => { if (UI.datosDesactualizados && UI.view !== 'importaciones' && !document.querySelector('.drawer,.modal-w')) { UI.datosDesactualizados = false; try { await cargarDatos(); } catch (e) {} } }, 15 * 60 * 1000);
}

(async function iniciar() {
  const h = (location.hash || '').slice(1); if (VIEWS[h]) UI.view = h;
  eventosGlobales();
  const d = await api.reanudar();
  if (d) await entrar(); else pantallaLogin('login');
})();
