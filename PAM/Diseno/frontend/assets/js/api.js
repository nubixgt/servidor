/* =====================================================================
   Cliente de la API (/api/v1). El token de acceso vive solo en memoria;
   la renovación usa una cookie httpOnly que el navegador envía sola.
   ===================================================================== */
const api = (() => {
  const BASE = '/api/v1';
  let token = null, usuario = null, renovando = null;

  async function renovar() {
    if (!renovando) {
      renovando = fetch(BASE + '/auth/refresh', { method: 'POST', credentials: 'same-origin' })
        .then(async r => { if (!r.ok) throw new Error('sin sesión'); const d = await r.json(); token = d.access_token; usuario = d.usuario; return d; })
        .finally(() => { setTimeout(() => { renovando = null; }, 0); });
    }
    return renovando;
  }

  async function pedir(metodo, ruta, { params, cuerpo, archivo, crudo } = {}) {
    let url = BASE + ruta;
    if (params) {
      const q = new URLSearchParams();
      for (const [k, v] of Object.entries(params)) if (v !== null && v !== undefined && v !== '') q.set(k, v);
      const s = q.toString(); if (s) url += '?' + s;
    }
    const intentar = async () => {
      const h = { 'X-Requested-With': 'fetch' };
      if (token) h.Authorization = 'Bearer ' + token;
      let body;
      if (archivo) body = archivo;               // FormData
      else if (cuerpo !== undefined) { h['Content-Type'] = 'application/json'; body = JSON.stringify(cuerpo); }
      return fetch(url, { method: metodo, headers: h, body, credentials: 'same-origin' });
    };
    let r = await intentar();
    if (r.status === 401 && !ruta.startsWith('/auth/')) {
      try { await renovar(); r = await intentar(); } catch (e) { api.alExpirar && api.alExpirar(); throw new ErrorApi(401, 'La sesión expiró. Ingrese de nuevo.'); }
    }
    if (crudo) { if (!r.ok) throw new ErrorApi(r.status, await mensaje(r)); return r; }
    if (r.status === 204 || r.status === 304) return null;
    if (!r.ok) throw new ErrorApi(r.status, await mensaje(r));
    return r.json();
  }

  async function mensaje(r) {
    try { const d = await r.json(); if (typeof d.detail === 'string') return d.detail;
      if (Array.isArray(d.detail)) return d.detail.map(x => (x.loc ? x.loc.slice(-1)[0] + ': ' : '') + x.msg).join(' · '); }
    catch (e) {}
    return r.status === 403 ? 'No tiene permiso para esta acción' : r.status >= 500 ? 'Error del servidor. Intente de nuevo o contacte a soporte.' : 'Solicitud no válida';
  }

  async function descargar(ruta, cuerpo, nombre) {
    const r = await pedir(cuerpo ? 'POST' : 'GET', ruta, { cuerpo, crudo: true });
    const blob = await r.blob();
    const cd = r.headers.get('content-disposition') || '';
    const m = cd.match(/filename="?([^"]+)"?/);
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = (m && m[1]) || nombre || 'descarga';
    document.body.appendChild(a); a.click(); setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
  }

  return {
    get: (r, params) => pedir('GET', r, { params }),
    post: (r, cuerpo) => pedir('POST', r, { cuerpo }),
    put: (r, cuerpo) => pedir('PUT', r, { cuerpo }),
    patch: (r, cuerpo) => pedir('PATCH', r, { cuerpo }),
    subir: (r, fd) => pedir('POST', r, { archivo: fd }),
    descargar,
    async login(u, c) {
      const r = await fetch(BASE + '/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ usuario: u, clave: c }) });
      if (!r.ok) throw new ErrorApi(r.status, await mensaje(r));
      const d = await r.json(); token = d.access_token; usuario = d.usuario; return d;
    },
    async reanudar() { try { const d = await renovar(); return d; } catch (e) { return null; } },
    async logout() { try { await fetch(BASE + '/auth/logout', { method: 'POST', credentials: 'same-origin' }); } catch (e) {} token = null; usuario = null; },
    usuario: () => usuario,
    _token: () => token,
    setUsuario: (u) => { usuario = u; },
    alExpirar: null,
  };
})();

class ErrorApi extends Error { constructor(status, msg) { super(msg); this.status = status; } }
const PUEDE = (p) => !!(api.usuario() && api.usuario().permisos.includes(p));
