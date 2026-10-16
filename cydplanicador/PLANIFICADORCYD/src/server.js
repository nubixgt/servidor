'use strict';
const config = require('./config');
const db = require('./db');

db.open();
const d = db.get();
// Primer administrador opcional mediante variables de entorno (sólo si aún no existe ningún administrador)
if (process.env.ADMIN_USERNAME && process.env.ADMIN_PASSWORD) {
  const { hashPassword, passwordProblems } = require('./util');
  const exists = d.prepare("SELECT COUNT(*) n FROM users WHERE role = 'admin'").get().n;
  if (!exists) {
    const probs = passwordProblems(process.env.ADMIN_PASSWORD);
    if (probs.length) { console.error('[inicio] ADMIN_PASSWORD no cumple los requisitos:', probs.join(' ')); process.exit(1); }
    d.prepare("INSERT INTO users(username, username_key, password_hash, full_name, role, must_change_password) VALUES (?,?,?,?, 'admin', 1)")
      .run(process.env.ADMIN_USERNAME, process.env.ADMIN_USERNAME.toLowerCase(), hashPassword(process.env.ADMIN_PASSWORD), process.env.ADMIN_FULL_NAME || 'Administración Colegio CyD');
    console.log(`[inicio] Administrador inicial creado: ${process.env.ADMIN_USERNAME} (deberá cambiar la contraseña al ingresar)`);
  }
}
// Limpieza periódica de sesiones vencidas
setInterval(() => {
  d.prepare('DELETE FROM sessions WHERE expires_at < ?').run(new Date().toISOString());
  require('./auth').pruneAttempts();
}, 3600e3).unref();

const { createApp } = require('./app');
const server = createApp().listen(config.port, config.host, () => {
  console.log(`Planificación Docente CyD escuchando en http://${config.host}:${config.port}${config.basePath}`);
  if (!require('./mail').enabled()) console.log('[aviso] Correo no configurado (SMTP_HOST, SMTP_FROM, PUBLIC_URL): la recuperación será administrativa.');
});

// Cierre ordenado: termina las peticiones en curso y cierra la base antes de salir
function shutdown(signal) {
  console.log(`[cierre] ${signal} recibido`);
  server.close(() => { try { db.get().close(); } catch { /* ya cerrada */ } process.exit(0); });
  setTimeout(() => process.exit(1), 10e3).unref();
}
process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
process.on('unhandledRejection', err => console.error('[promesa rechazada]', err));
