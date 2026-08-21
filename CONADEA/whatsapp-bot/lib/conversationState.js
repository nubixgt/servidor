const config = require('../config');

// Estado de conversación por JID, solo en memoria: se pierde si el proceso
// se reinicia. Aceptable porque el menú es corto — el usuario simplemente
// escribe de nuevo y arranca desde "root".
const estados = new Map();

function getState(jid) {
    // actualizadoEn: 0 (no Date.now()) para una conversación nueva — si no,
    // el primer mensaje de cualquier chat nuevo siempre queda dentro de la
    // ventana del filtro anti-abuso ("ahora - ahora" ≈ 0) y se descarta solo.
    return estados.get(jid) || { paso: 'root', datos: {}, actualizadoEn: 0 };
}

function setState(jid, estado) {
    estados.set(jid, { ...estado, actualizadoEn: Date.now() });
}

function clearState(jid) {
    estados.delete(jid);
}

// Barrido periódico: olvida conversaciones abandonadas a medio menú.
setInterval(() => {
    const limite = Date.now() - config.inactividadMinutos * 60 * 1000;
    for (const [jid, estado] of estados.entries()) {
        if (estado.actualizadoEn < limite) {
            estados.delete(jid);
        }
    }
}, 5 * 60 * 1000);

module.exports = { getState, setState, clearState };
