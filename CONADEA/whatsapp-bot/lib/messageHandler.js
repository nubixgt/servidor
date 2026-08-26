const backend = require('./backendClient');
const menu = require('./menu');
const { parsearMinutos, parsearHora, parsearDias } = require('./parsers');
const { getState, setState, clearState } = require('./conversationState');

// Evita procesar ráfagas del mismo chat demasiado rápido (mitiga el riesgo
// de que el número sea marcado por abuso, al ser una librería no oficial).
const RATE_LIMIT_MS = 2000;

const MINUTOS_POSPONER = 30;
const PASOS_WIZARD = ['eligiendo_curso', 'preguntando_minutos', 'preguntando_hora', 'preguntando_dias'];

function extraerTexto(message) {
    return (message.conversation || message.extendedTextMessage?.text || '').trim();
}

async function handleIncomingMessage(sock, { messages, type }) {
    if (type !== 'notify') return;

    for (const msg of messages) {
        try {
            await procesarMensaje(sock, msg);
        } catch (err) {
            console.error('Error procesando mensaje de WhatsApp:', err);
        }
    }
}

async function procesarMensaje(sock, msg) {
    const jid = msg.key.remoteJid;
    if (!jid || msg.key.fromMe || jid.endsWith('@g.us') || jid === 'status@broadcast' || !msg.message) {
        return;
    }

    const estadoActual = getState(jid);
    if (Date.now() - estadoActual.actualizadoEn < RATE_LIMIT_MS) return;

    // Si el remitente usa el sistema nuevo de LID de WhatsApp (privacidad),
    // "jid" es un identificador opaco (termina en @lid), no el número real
    // — Baileys igual expone el número real en key.senderPn cuando existe.
    const jidTelefono = msg.key.senderPn || jid;
    const telefono = jidTelefono.split('@')[0].replace(/\D/g, '');
    const responder = (texto) => sock.sendMessage(jid, { text: texto });
    const textoOriginal = extraerTexto(msg.message).trim();
    const comando = textoOriginal.toLowerCase();
    const datos = estadoActual.datos || {};
    const usuario = datos.usuario;

    // "0"/"salir" siempre reinician el flujo, sin importar en qué paso esté.
    if (comando === '0' || comando === 'salir') {
        clearState(jid);
        await responder(menu.textoDespedida(usuario?.nombre_completo));
        return;
    }

    if (!usuario) {
        await manejarSinRegistrar(telefono, jid, responder);
        return;
    }

    // Comandos libres por palabra clave (ver mockup, cuadro 5: "cambiar mi
    // hora", "pausar avisos", etc.), reconocidos siempre que el usuario no
    // esté a mitad del wizard de horario (ahí cualquier texto es su respuesta
    // a la pregunta actual, no un comando).
    if (!PASOS_WIZARD.includes(estadoActual.paso)) {
        const manejado = await manejarComandoLibre(comando, jid, telefono, usuario, datos, responder);
        if (manejado) return;
    }

    switch (estadoActual.paso) {
        case 'eligiendo_curso':
            await manejarEleccionCurso(textoOriginal, jid, telefono, usuario, datos, responder);
            return;
        case 'preguntando_minutos':
            await manejarMinutos(textoOriginal, jid, usuario, datos, responder);
            return;
        case 'preguntando_hora':
            await manejarHora(textoOriginal, jid, usuario, datos, responder);
            return;
        case 'preguntando_dias':
            await manejarDias(textoOriginal, jid, telefono, usuario, datos, responder);
            return;
        default:
            await mostrarEstado(jid, usuario, telefono, responder);
    }
}

async function manejarSinRegistrar(telefono, jid, responder) {
    let usuario;
    try {
        usuario = await backend.resolverUsuario(telefono);
    } catch (err) {
        console.error('Error resolviendo usuario:', err);
        await responder(menu.textoErrorGenerico());
        return;
    }

    if (!usuario) {
        await responder(menu.textoNoRegistrado());
        clearState(jid);
        return;
    }

    await responder(menu.textoBienvenida(usuario.nombre_completo));
    await mostrarEstado(jid, usuario, telefono, responder);
}

async function mostrarEstado(jid, usuario, telefono, responder) {
    let horarios;
    try {
        horarios = await backend.obtenerHorarios(telefono);
    } catch (err) {
        console.error('Error obteniendo horarios:', err);
        await responder(menu.textoErrorGenerico());
        return;
    }
    await responder(menu.textoEstadoYComandos(usuario.nombre_completo, horarios));
    setState(jid, { paso: 'idle', datos: { usuario, horarios } });
}

/** @returns {Promise<boolean>} true si el texto disparó un comando y ya se respondió */
async function manejarComandoLibre(comando, jid, telefono, usuario, datos, responder) {
    if (comando.includes('horario') || comando.includes('cambiar')) {
        await iniciarWizardHorario(jid, telefono, usuario, responder);
        return true;
    }
    if (comando.includes('ayuda')) {
        await responder(menu.textoSoporte(usuario.nombre_completo));
        return true;
    }
    if (comando.includes('mas tarde') || comando.includes('más tarde')) {
        await conCursoDelContexto(datos, jid, usuario, telefono, responder, async (cursoId) => {
            await backend.posponerHorario({ telefono, cursoId, minutos: MINUTOS_POSPONER });
            await responder(menu.textoPospuesto(MINUTOS_POSPONER));
        });
        return true;
    }
    if (comando.includes('pausar')) {
        await conCursoDelContexto(datos, jid, usuario, telefono, responder, async (cursoId, cursoTitulo) => {
            await backend.actualizarActivo({ telefono, cursoId, activo: false });
            await responder(menu.textoPausado(cursoTitulo));
        });
        return true;
    }
    if (comando.includes('reanudar')) {
        await conCursoDelContexto(datos, jid, usuario, telefono, responder, async (cursoId, cursoTitulo) => {
            await backend.actualizarActivo({ telefono, cursoId, activo: true });
            await responder(menu.textoReanudado(cursoTitulo));
        });
        return true;
    }
    if (comando.includes('continuar') || comando.includes('abrir')) {
        await conCursoDelContexto(datos, jid, usuario, telefono, responder, async (cursoId) => {
            await responder(menu.urlCurso(cursoId));
        });
        return true;
    }
    return false;
}

/**
 * Varios comandos libres ("más tarde", "pausar avisos"...) aplican sobre "el
 * curso del que se está hablando" — el del último recordatorio recibido si
 * hay uno, o si el usuario solo tiene un curso configurado, ese mismo.
 */
async function conCursoDelContexto(datos, jid, usuario, telefono, responder, accion) {
    let cursoId = datos.ultimoCursoId;
    let cursoTitulo = datos.ultimoCursoTitulo;

    if (!cursoId) {
        let horarios;
        try {
            horarios = await backend.obtenerHorarios(telefono);
        } catch (err) {
            console.error('Error obteniendo horarios:', err);
            await responder(menu.textoErrorGenerico());
            return;
        }
        if (horarios.length !== 1) {
            await responder(menu.textoSinHorarioParaComando());
            return;
        }
        cursoId = horarios[0].curso_id;
        cursoTitulo = horarios[0].curso_titulo;
    }

    try {
        await accion(cursoId, cursoTitulo);
        setState(jid, { paso: 'idle', datos: { ...datos, usuario, ultimoCursoId: cursoId, ultimoCursoTitulo: cursoTitulo } });
    } catch (err) {
        console.error('Error aplicando comando sobre horario:', err);
        await responder(menu.textoErrorGenerico());
    }
}

async function iniciarWizardHorario(jid, telefono, usuario, responder) {
    let cursos;
    try {
        cursos = await backend.cursosDisponibles();
    } catch (err) {
        console.error('Error obteniendo cursos:', err);
        await responder(menu.textoErrorGenerico());
        return;
    }

    await responder(menu.textoListaCursos(cursos));
    setState(jid, { paso: 'eligiendo_curso', datos: { usuario, cursos, telefono } });
}

async function manejarEleccionCurso(texto, jid, telefono, usuario, datos, responder) {
    const indice = parseInt(texto, 10) - 1;
    const curso = datos.cursos?.[indice];
    if (!curso) {
        await responder(menu.textoCursoInvalido());
        return;
    }

    await responder(menu.textoPreguntaMinutos(curso.titulo));
    setState(jid, { paso: 'preguntando_minutos', datos: { usuario, telefono, curso } });
}

async function manejarMinutos(texto, jid, usuario, datos, responder) {
    const minutos = parsearMinutos(texto);
    if (minutos === null) {
        await responder(menu.textoMinutosInvalidos());
        return;
    }

    await responder(menu.textoPreguntaHora());
    setState(jid, { paso: 'preguntando_hora', datos: { ...datos, minutos } });
}

async function manejarHora(texto, jid, usuario, datos, responder) {
    const hora = parsearHora(texto);
    if (hora === null) {
        await responder(menu.textoHoraInvalida());
        return;
    }

    await responder(menu.textoPreguntaDias());
    setState(jid, { paso: 'preguntando_dias', datos: { ...datos, hora } });
}

async function manejarDias(texto, jid, telefono, usuario, datos, responder) {
    const dias = parsearDias(texto);
    if (dias === null) {
        await responder(menu.textoDiasInvalidos());
        return;
    }

    const { curso, minutos, hora } = datos;
    try {
        await backend.guardarHorario({ telefono, cursoId: curso.id, dias, hora, duracionMinutos: minutos });
    } catch (err) {
        console.error('Error guardando horario:', err);
        await responder(menu.textoErrorGenerico());
        return;
    }

    await responder(menu.textoResumenHorario({ cursoTitulo: curso.titulo, dias, hora, duracionMinutos: minutos }));
    setState(jid, { paso: 'idle', datos: { usuario, ultimoCursoId: curso.id, ultimoCursoTitulo: curso.titulo } });
}

module.exports = { handleIncomingMessage };
