const backend = require('./backendClient');
const menu = require('./menu');
const { setState, getState } = require('./conversationState');

const INTERVALO_MS = 60 * 1000;

let enCurso = false;
let sockActual = null;
let intervaloIniciado = false;

async function revisarYEnviar() {
    if (enCurso || !sockActual) return; // evita solapar si una vuelta tarda más de 60s
    enCurso = true;

    try {
        const debidos = await backend.horariosDebidos();
        for (const fila of debidos) {
            await enviarRecordatorio(sockActual, fila);
        }
    } catch (err) {
        console.error('Error revisando recordatorios de horario:', err);
    } finally {
        enCurso = false;
    }
}

async function enviarRecordatorio(sock, fila) {
    const { telefono, nombre_completo, curso_id, curso_titulo, hora, duracion_minutos } = fila;
    const jid = `${telefono}@s.whatsapp.net`;

    try {
        await sock.sendMessage(jid, {
            text: menu.textoRecordatorio({ cursoTitulo: curso_titulo, hora, duracionMinutos: duracion_minutos, cursoId: curso_id }),
        });
        await backend.marcarNotificado({ telefono, cursoId: curso_id });

        // Para que "más tarde"/"pausar avisos" (comandos libres sin mencionar
        // el curso) sepan a cuál de los horarios del usuario se refieren.
        const estado = getState(jid);
        setState(jid, {
            paso: 'idle',
            datos: { ...estado.datos, usuario: estado.datos?.usuario || { nombre_completo }, ultimoCursoId: curso_id, ultimoCursoTitulo: curso_titulo },
        });
    } catch (err) {
        console.error(`Error mandando recordatorio a ${telefono} (curso ${curso_id}):`, err);
    }
}

// Se llama cada vez que la conexión de Baileys abre (incluyendo
// reconexiones) — actualiza qué socket usar para enviar, pero el
// setInterval mismo solo arranca una vez.
function iniciar(sock) {
    sockActual = sock;
    if (intervaloIniciado) return;
    intervaloIniciado = true;
    setInterval(revisarYEnviar, INTERVALO_MS);
}

module.exports = { iniciar };
