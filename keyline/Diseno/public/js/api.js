const Api = (() => {
  async function req(method, url, body, isForm) {
    const opts = { method, credentials: 'include', headers: {} };
    if (body && !isForm) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    } else if (body && isForm) {
      opts.body = body;
    }
    const res = await fetch(url, opts);
    let data = null;
    try { data = await res.json(); } catch (e) { /* respuesta sin cuerpo */ }
    if (!res.ok) {
      const err = new Error((data && data.error) || `Error ${res.status}`);
      err.status = res.status;
      throw err;
    }
    return data;
  }

  return {
    get: (url) => req('GET', url),
    post: (url, body) => req('POST', url, body),
    put: (url, body) => req('PUT', url, body),
    del: (url) => req('DELETE', url),
    postForm: (url, formData) => req('POST', url, formData, true),

    login: (email, password) => req('POST', '/api/auth/login', { email, password }),
    logout: () => req('POST', '/api/auth/logout'),
    me: () => req('GET', '/api/auth/me'),
    cambiarPassword: (actual, nueva) => req('POST', '/api/auth/cambiar-password', { actual, nueva }),

    constantes: () => req('GET', '/api/constantes'),

    listarParcelas: (params = {}) => {
      const qs = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== '' && v !== undefined && v !== null)).toString();
      return req('GET', `/api/parcelas${qs ? '?' + qs : ''}`);
    },
    obtenerParcela: (id) => req('GET', `/api/parcelas/${id}`),
    crearParcela: (data) => req('POST', '/api/parcelas', data),
    actualizarParcela: (id, data) => req('PUT', `/api/parcelas/${id}`, data),
    eliminarParcela: (id) => req('DELETE', `/api/parcelas/${id}`),
    revisarParcela: (id, estadoValidacion, comentario) => req('POST', `/api/parcelas/${id}/revision`, { estadoValidacion, comentario }),
    subirFotos: (id, formData) => req('POST', `/api/parcelas/${id}/fotos`, formData, true),
    eliminarFoto: (id, fotoId) => req('DELETE', `/api/parcelas/${id}/fotos/${fotoId}`),

    dashboardResumen: () => req('GET', '/api/dashboard/resumen'),
    actividad: () => req('GET', '/api/dashboard/actividad'),

    listarUsuarios: () => req('GET', '/api/usuarios'),
    crearUsuario: (data) => req('POST', '/api/usuarios', data),
    actualizarUsuario: (id, data) => req('PUT', `/api/usuarios/${id}`, data),
    eliminarUsuario: (id) => req('DELETE', `/api/usuarios/${id}`),
  };
})();
