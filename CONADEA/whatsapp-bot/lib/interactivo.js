const { generateWAMessageFromContent, proto } = require('@whiskeysockets/baileys');

// Los mensajes de botones/listas "clásicos" de Baileys (buttonsMessage,
// listMessage) ya casi no renderizan en WhatsApp normal (no Business API) —
// WhatsApp los retiró para cuentas no verificadas. Lo que sí sigue
// funcionando en la app normal es el "interactive message" con
// nativeFlowMessage, que no tiene un helper oficial en Baileys: hay que
// armar el proto a mano y mandarlo con relayMessage en vez de sendMessage.
async function enviarInteractivo(sock, jid, { texto, pie, botones }) {
    const mensaje = generateWAMessageFromContent(
        jid,
        {
            viewOnceMessage: {
                message: {
                    messageContextInfo: {
                        deviceListMetadata: {},
                        deviceListMetadataVersion: 2,
                    },
                    interactiveMessage: proto.Message.InteractiveMessage.create({
                        body: proto.Message.InteractiveMessage.Body.create({ text: texto }),
                        footer: pie ? proto.Message.InteractiveMessage.Footer.create({ text: pie }) : undefined,
                        nativeFlowMessage: proto.Message.InteractiveMessage.NativeFlowMessage.create({
                            buttons: botones.map((b) => ({
                                name: b.tipo,
                                buttonParamsJson: JSON.stringify(b.params),
                            })),
                        }),
                    }),
                },
            },
        },
        {}
    );

    await sock.relayMessage(jid, mensaje.message, { messageId: mensaje.key.id });
}

// Botón que manda una respuesta de vuelta al bot (llega como
// interactiveResponseMessage en messages.upsert).
function botonRespuestaRapida(id, textoVisible) {
    return { tipo: 'quick_reply', params: { display_text: textoVisible, id } };
}

// Botón que abre un link en el navegador — no genera respuesta al bot.
function botonAbrirLink(url, textoVisible) {
    return { tipo: 'cta_url', params: { display_text: textoVisible, url } };
}

// Lista de selección única (reemplaza al viejo listMessage). WhatsApp no
// soporta selección múltiple en listas, solo una fila a la vez.
function botonLista(textoBoton, secciones) {
    return {
        tipo: 'single_select',
        params: {
            title: textoBoton,
            sections: secciones.map((s) => ({
                title: s.titulo,
                rows: s.filas.map((f) => ({ header: '', title: f.titulo, description: f.descripcion || '', id: f.id })),
            })),
        },
    };
}

/**
 * Si el usuario tocó un botón interactivo, Baileys lo entrega como
 * interactiveResponseMessage (no como conversation/extendedTextMessage). El
 * id elegido viene serializado dentro de nativeFlowResponseMessage.paramsJson.
 * @returns {string|null} el id del botón/fila elegida, o null si el mensaje no es una respuesta interactiva.
 */
function extraerRespuestaInteractiva(message) {
    const paramsJson = message?.interactiveResponseMessage?.nativeFlowResponseMessage?.paramsJson;
    if (!paramsJson) return null;
    try {
        return JSON.parse(paramsJson).id ?? null;
    } catch {
        return null;
    }
}

module.exports = {
    enviarInteractivo,
    botonRespuestaRapida,
    botonAbrirLink,
    botonLista,
    extraerRespuestaInteractiva,
};
