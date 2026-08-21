const { downloadMediaMessage } = require('@whiskeysockets/baileys');
const backend = require('./backendClient');
const menu = require('./menu');
const { getState, setState, clearState } = require('./conversationState');

// Evita procesar ráfagas del mismo chat demasiado rápido (mitiga el riesgo
// de que el número sea marcado por abuso, al ser una librería no oficial).
const RATE_LIMIT_MS = 2000;

function extraerTexto(message) {
    return (
        message.conversation ||
        message.extendedTextMessage?.text ||
        message.imageMessage?.caption ||
        ''
    ).trim();
}

function detectarAdjunto(message) {
    if (message.imageMessage) return 'imagen';
    if (message.audioMessage) return 'audio';
    if (message.locationMessage) return 'ubicacion';
    return null;
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
    const comando = extraerTexto(msg.message).trim().toLowerCase();
    const usuario = estadoActual.datos?.usuario;

    // "menu"/"0" siempre reinician el flujo, sin importar en qué paso esté.
    if (comando === '0' || comando === 'salir') {
        clearState(jid);
        await responder(menu.textoDespedida(usuario?.nombre_completo));
        return;
    }

    if (estadoActual.paso === 'consulta_esperando_adjunto') {
        await manejarAdjuntoConsulta(sock, msg, jid, telefono, usuario, responder);
        return;
    }

    if (estadoActual.paso !== 'menu' || comando === 'menu') {
        await saludarYMostrarMenu(jid, telefono, responder);
        return;
    }

    await manejarOpcionMenu(comando, jid, telefono, usuario, responder);
}

async function saludarYMostrarMenu(jid, telefono, responder) {
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
        setState(jid, { paso: 'root', datos: {} });
        return;
    }

    await responder(`${menu.textoBienvenida(usuario.nombre_completo)}\n\n${menu.textoMenu()}`);
    setState(jid, { paso: 'menu', datos: { usuario } });
}

async function manejarOpcionMenu(comando, jid, telefono, usuario, responder) {
    switch (comando) {
        case '1':
        case '2': {
            let progreso;
            try {
                progreso = await backend.obtenerProgreso(telefono);
            } catch (err) {
                console.error('Error obteniendo progreso:', err);
                await responder(menu.textoErrorGenerico());
                return;
            }
            const nombre = usuario?.nombre_completo;
            await responder(comando === '1' ? menu.textoCursos(nombre, progreso) : menu.textoResumen(nombre, progreso));
            setState(jid, { paso: 'menu', datos: { usuario } });
            return;
        }
        case '3':
            await responder(menu.textoPideAdjunto(usuario?.nombre_completo));
            setState(jid, { paso: 'consulta_esperando_adjunto', datos: { usuario } });
            return;
        case '4':
            await responder(menu.textoSoporte(usuario?.nombre_completo));
            setState(jid, { paso: 'menu', datos: { usuario } });
            return;
        default:
            await responder(`${menu.textoOpcionInvalida(usuario?.nombre_completo)}\n\n${menu.textoMenu()}`);
            setState(jid, { paso: 'menu', datos: { usuario } });
    }
}

async function manejarAdjuntoConsulta(sock, msg, jid, telefono, usuario, responder) {
    const tipoAdjunto = detectarAdjunto(msg.message);
    const mensaje = extraerTexto(msg.message) || undefined;

    try {
        if (tipoAdjunto === 'ubicacion') {
            const { degreesLatitude, degreesLongitude } = msg.message.locationMessage;
            await backend.registrarConsulta({ telefono, tipo: 'ubicacion', lat: degreesLatitude, lng: degreesLongitude, mensaje });
        } else if (tipoAdjunto === 'imagen' || tipoAdjunto === 'audio') {
            const buffer = await downloadMediaMessage(msg, 'buffer', {}, { reuploadRequest: sock.updateMediaMessage });
            const mimetype = msg.message[tipoAdjunto === 'imagen' ? 'imageMessage' : 'audioMessage']?.mimetype;
            await backend.registrarConsulta({ telefono, tipo: tipoAdjunto, buffer, mimetype, mensaje });
        } else if (mensaje) {
            await backend.registrarConsulta({ telefono, tipo: 'texto', mensaje });
        } else {
            await responder(menu.textoPideAdjunto(usuario?.nombre_completo));
            return;
        }

        await responder(`${menu.textoConsultaRecibida(usuario?.nombre_completo)}\n\n${menu.textoMenu()}`);
        setState(jid, { paso: 'menu', datos: { usuario } });
    } catch (err) {
        console.error('Error registrando consulta técnica:', err);
        await responder(menu.textoErrorGenerico());
        // Se queda en el mismo paso para no perder el hilo de la consulta.
    }
}

module.exports = { handleIncomingMessage };
