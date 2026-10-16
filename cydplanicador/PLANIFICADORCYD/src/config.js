'use strict';
const path = require('path');

// Carga opcional de un archivo .env sencillo (CLAVE=valor) sin dependencias externas.
(function loadEnv() {
  const fs = require('fs');
  const file = process.env.ENV_FILE || path.join(__dirname, '..', '.env');
  if (!fs.existsSync(file)) return;
  for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
    if (!m || line.trim().startsWith('#')) continue;
    let v = m[2];
    if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) v = v.slice(1, -1);
    if (process.env[m[1]] === undefined) process.env[m[1]] = v;
  }
})();

const env = process.env;

// Subruta pública (p. ej. /cydplanicador) cuando la app se publica dentro de un dominio compartido.
function normalizeBase(v) {
  const s = String(v || '').trim().replace(/\/+$/, '');
  if (!s) return '';
  if (!/^\/[A-Za-z0-9._~\/-]*$/.test(s)) throw new Error('BASE_PATH no válido: use algo como /cydplanicador');
  return s;
}

module.exports = {
  port: parseInt(env.PORT, 10) || 3000,
  basePath: normalizeBase(env.BASE_PATH),
  host: env.HOST || '0.0.0.0',
  dbFile: env.DB_FILE || path.join(__dirname, '..', 'data', 'cyd.db'),
  backupDir: env.BACKUP_DIR || path.join(__dirname, '..', 'backups'),
  // URL pública usada en los enlaces de correo (recuperación y verificación)
  publicUrl: (env.PUBLIC_URL || '').replace(/\/$/, ''),
  // Cookies "Secure": activar cuando el sitio se sirve por HTTPS
  secureCookies: env.SECURE_COOKIES === '1' || env.SECURE_COOKIES === 'true',
  trustProxy: env.TRUST_PROXY === '1' || env.TRUST_PROXY === 'true',
  sessionHours: parseInt(env.SESSION_HOURS || '12', 10),
  smtp: {
    host: env.SMTP_HOST || '',
    port: parseInt(env.SMTP_PORT || '587', 10),
    secure: env.SMTP_SECURE === '1' || env.SMTP_SECURE === 'true',
    user: env.SMTP_USER || '',
    pass: env.SMTP_PASS || '',
    from: env.SMTP_FROM || '',
  },
  get mailEnabled() {
    return Boolean(this.smtp.host && this.smtp.from && this.publicUrl);
  },
};
