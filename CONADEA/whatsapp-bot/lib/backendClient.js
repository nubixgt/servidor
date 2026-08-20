const config = require('../config');

async function get(path) {
    const res = await fetch(`${config.backendBaseUrl}${path}`, {
        headers: { 'x-api-key': config.apiKey },
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(body.message || `Backend respondió ${res.status}`);
        err.status = res.status;
        throw err;
    }
    return body.data;
}

/** @returns {Promise<object|null>} datos del usuario, o null si el teléfono no está registrado */
async function resolverUsuario(telefono) {
    try {
        return await get(`/asistente/usuario?telefono=${encodeURIComponent(telefono)}`);
    } catch (err) {
        if (err.status === 404) return null;
        throw err;
    }
}

async function obtenerProgreso(telefono) {
    return get(`/asistente/progreso?telefono=${encodeURIComponent(telefono)}`);
}

/**
 * @param {object} datos
 * @param {string} datos.telefono
 * @param {'imagen'|'audio'|'ubicacion'|'texto'} datos.tipo
 * @param {string} [datos.mensaje] caption o texto libre
 * @param {Buffer} [datos.buffer] contenido del adjunto (imagen/audio)
 * @param {string} [datos.mimetype]
 * @param {number} [datos.lat]
 * @param {number} [datos.lng]
 */
async function registrarConsulta(datos) {
    const form = new FormData();
    form.append('telefono', datos.telefono);
    form.append('tipo', datos.tipo);
    if (datos.mensaje) form.append('mensaje', datos.mensaje);
    if (datos.lat !== undefined && datos.lng !== undefined) {
        form.append('lat', String(datos.lat));
        form.append('lng', String(datos.lng));
    }
    if (datos.buffer) {
        form.append('archivo', new Blob([datos.buffer], { type: datos.mimetype || 'application/octet-stream' }));
    }

    const res = await fetch(`${config.backendBaseUrl}/asistente/consultas`, {
        method: 'POST',
        headers: { 'x-api-key': config.apiKey },
        body: form,
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(body.message || `Backend respondió ${res.status}`);
    }
    return body.data;
}

module.exports = { resolverUsuario, obtenerProgreso, registrarConsulta };
