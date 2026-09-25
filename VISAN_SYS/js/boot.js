// Arranque: valida la sesión, carga la matriz desde el servidor y luego inicia la aplicación.

/** Llamada al API con manejo uniforme de errores y sesión expirada. */
window.api = async function api(url, { json, form } = {}) {
  const opt = { method: 'GET', credentials: 'same-origin', headers: { 'X-VISAN': '1' } };
  if (json !== undefined) {
    opt.method = 'POST';
    opt.headers['Content-Type'] = 'application/json';
    opt.body = JSON.stringify(json);
  }
  if (form) {
    opt.method = 'POST';
    opt.body = form;
  }
  let res;
  try {
    res = await fetch(url, opt);
  } catch (e) {
    throw new Error('No hay conexión con el servidor.');
  }
  let data;
  try {
    data = await res.json();
  } catch (e) {
    data = { ok: false, error: `Respuesta inválida del servidor (${res.status}).` };
  }
  if (res.status === 401 && !url.includes('a=login')) {
    location.replace('login.html');
    throw new Error('Tu sesión expiró. Vuelve a ingresar.');
  }
  if (!res.ok || data.ok === false) throw new Error(data.error || `Error ${res.status}`);
  return data;
};

// Cache-busting: sin esto, el navegador puede quedarse con una copia vieja de
// estos scripts (ej. bodegas_data.js) después de un despliegue, mostrando datos
// desactualizados hasta que el usuario borre caché a mano.
const BUILD_STAMP = Date.now();
function loadScript(src) {
  return new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = src + (src.includes('?') ? '&' : '?') + 'v=' + BUILD_STAMP;
    s.onload = resolve;
    s.onerror = () => reject(new Error(`No se pudo cargar ${src}`));
    document.body.appendChild(s);
  });
}

(async function boot() {
  const loader = document.getElementById('boot-loader');
  try {
    const me = await api('api/auth.php?a=me');
    const datos = await api('api/data.php');
    window.CURRENT_USER = me.usuario;
    window.DATA = datos.filas;
    window.VISAN_META = datos.meta;
    window.VISAN_BODEGAS = datos.bodegas;
    await loadScript('bodegas_data.js');
    await loadScript('js/app.js');
    await loadScript('js/admin.js');
    loader.classList.add('done');
    setTimeout(() => loader.remove(), 400);
  } catch (e) {
    if (/sesión/i.test(e.message)) return;
    loader.querySelector('.boot-msg').textContent = e.message;
    loader.classList.add('failed');
  }
})();
