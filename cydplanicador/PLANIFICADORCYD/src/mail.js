'use strict';
const config = require('./config');

let transport = null;
function getTransport() {
  if (!config.mailEnabled) return null;
  if (!transport) {
    const nodemailer = require('nodemailer');
    transport = nodemailer.createTransport({
      host: config.smtp.host,
      port: config.smtp.port,
      secure: config.smtp.secure,
      auth: config.smtp.user ? { user: config.smtp.user, pass: config.smtp.pass } : undefined,
    });
  }
  return transport;
}

/** Envía un correo. Lanza error si el servidor SMTP lo rechaza; nunca simula un envío. */
async function send(to, subject, text) {
  const t = getTransport();
  if (!t) throw new Error('El envío de correo no está configurado en este servidor.');
  await t.sendMail({ from: config.smtp.from, to, subject, text });
}

module.exports = { send, enabled: () => config.mailEnabled };
