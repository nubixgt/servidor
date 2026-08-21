const express = require('express');
const path = require('path');
const pino = require('pino');
const qrcode = require('qrcode-terminal');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason
} = require('@whiskeysockets/baileys');

const config = require('./config');
const { handleIncomingMessage } = require('./lib/messageHandler');

const SESSION_DIR = path.join(__dirname, 'session');
const logger = pino({ level: 'silent' });

// Baileys a veces lanza rechazos de promesa no capturados en tareas de
// fondo (ej. timeout subiendo pre-keys) que no pasan por el evento
// connection.update — sin este handler, Node mata todo el proceso por un
// hipo transitorio en vez de dejar que la reconexión propia se encargue.
process.on('unhandledRejection', (err) => {
    console.error('Unhandled rejection (ignorada, la conexión sigue):', err?.message || err);
});

const pairPhoneArg = process.argv.find(a => a.startsWith('--phone='));
const pairPhone = pairPhoneArg ? pairPhoneArg.split('=')[1].replace(/\D/g, '') : null;

let sock = null;
let isReady = false;

async function startSocket() {
    const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);

    sock = makeWASocket({
        auth: state,
        logger
    });

    if (pairPhone && !state.creds.registered) {
        try {
            await new Promise(resolve => setTimeout(resolve, 4000));
            const code = await sock.requestPairingCode(pairPhone);
            console.log('\n>>> Código de vinculación: ' + code + ' <<<\n');
            console.log('En el teléfono: WhatsApp > Dispositivos vinculados > Vincular un dispositivo > "Vincular con número de teléfono", e ingresa ese código.\n');
        } catch (err) {
            console.error('No se pudo solicitar el código de vinculación:', err);
        }
    }

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('messages.upsert', (upsert) => {
        handleIncomingMessage(sock, upsert).catch((err) => {
            console.error('Error inesperado manejando mensajes entrantes:', err);
        });
    });

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr && !pairPhone) {
            console.log('Escanea este QR con WhatsApp (Dispositivos vinculados):');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'open') {
            isReady = true;
            console.log('WhatsApp conectado. Asistente AgroIA listo para recibir mensajes.');
        }

        if (connection === 'close') {
            isReady = false;
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            console.log('Conexión de WhatsApp cerrada. Reconectar:', shouldReconnect);
            if (shouldReconnect) {
                startSocket();
            }
        }
    });
}

startSocket();

const app = express();

// Passenger para Node.js manda la ruta completa (incluyendo el
// PassengerBaseURI configurado en cPanel) en vez de recortarla como hace
// con otros lenguajes, así que la quitamos nosotros mismos.
app.use((req, res, next) => {
    const prefix = '/CONADEA/whatsapp-bot';
    if (req.url.startsWith(prefix)) {
        req.url = req.url.slice(prefix.length) || '/';
    }
    next();
});

app.get('/health', (req, res) => {
    res.json({ status: 'ok', whatsappReady: isReady });
});

app.listen(config.port, () => {
    console.log(`Asistente WhatsApp de CONADEA escuchando en el puerto ${config.port}`);
});
