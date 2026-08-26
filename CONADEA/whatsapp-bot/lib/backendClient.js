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

async function post(path, datos) {
    const res = await fetch(`${config.backendBaseUrl}${path}`, {
        method: 'POST',
        headers: { 'x-api-key': config.apiKey, 'Content-Type': 'application/json' },
        body: JSON.stringify(datos),
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

/** @returns {Promise<Array<{id:number, titulo:string}>>} */
async function cursosDisponibles() {
    return get('/asistente/cursos');
}

/** @returns {Promise<Array<{curso_id:number, curso_titulo:string, dias:string, hora:string, duracion_minutos:number, activo:number}>>} */
async function obtenerHorarios(telefono) {
    return get(`/asistente/horarios?telefono=${encodeURIComponent(telefono)}`);
}

async function guardarHorario({ telefono, cursoId, dias, hora, duracionMinutos }) {
    return post('/asistente/horarios', { telefono, curso_id: cursoId, dias, hora, duracion_minutos: duracionMinutos });
}

async function posponerHorario({ telefono, cursoId, minutos }) {
    return post('/asistente/horarios/posponer', { telefono, curso_id: cursoId, minutos });
}

async function actualizarActivo({ telefono, cursoId, activo }) {
    return post('/asistente/horarios/activo', { telefono, curso_id: cursoId, activo });
}

/** @returns {Promise<Array<{usuario_id:number, telefono:string, nombre_completo:string, curso_id:number, curso_titulo:string, hora:string, duracion_minutos:number}>>} */
async function horariosDebidos() {
    return get('/asistente/horarios/debidos');
}

async function marcarNotificado({ telefono, cursoId }) {
    return post('/asistente/horarios/marcar-notificado', { telefono, curso_id: cursoId });
}

module.exports = {
    resolverUsuario,
    cursosDisponibles,
    obtenerHorarios,
    guardarHorario,
    posponerHorario,
    actualizarActivo,
    horariosDebidos,
    marcarNotificado,
};
