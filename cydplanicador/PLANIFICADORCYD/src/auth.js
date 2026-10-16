'use strict';
const config = require('./config');
const { get: db } = require('./db');
const { randomToken, sha256, HttpError } = require('./util');

const COOKIE = 'cyd_sid';

function parseCookies(header) {
  const out = {};
  for (const part of String(header || '').split(';')) {
    const i = part.indexOf('=');
    if (i < 0) continue;
    const k = part.slice(0, i).trim();
    try { out[k] = decodeURIComponent(part.slice(i + 1).trim()); } catch { /* ignorar */ }
  }
  return out;
}

function cookieAttrs(maxAgeSec) {
  return [
    `Path=${config.basePath || '/'}`, 'HttpOnly', 'SameSite=Lax',
    config.secureCookies ? 'Secure' : null,
    maxAgeSec != null ? `Max-Age=${maxAgeSec}` : null,
  ].filter(Boolean).join('; ');
}

// «Mantener sesión iniciada»: 30 días con cookie persistente. Sin ella, la cookie se borra al cerrar
// el navegador y la sesión vence a las SESSION_HOURS horas.
const REMEMBER_DAYS = 30;
function createSession(res, userId, userAgent, { remember = false } = {}) {
  const token = randomToken(32);
  const seconds = remember ? REMEMBER_DAYS * 86400 : config.sessionHours * 3600;
  const expires = new Date(Date.now() + seconds * 1000).toISOString();
  db().prepare('INSERT INTO sessions(id, user_id, expires_at, user_agent) VALUES (?,?,?,?)')
    .run(sha256(token), userId, expires, String(userAgent || '').slice(0, 200));
  res.setHeader('Set-Cookie', `${COOKIE}=${token}; ${cookieAttrs(remember ? seconds : null)}`);
}

function destroySession(req, res) {
  const token = parseCookies(req.headers.cookie)[COOKIE];
  if (token) db().prepare('DELETE FROM sessions WHERE id = ?').run(sha256(token));
  res.setHeader('Set-Cookie', `${COOKIE}=; ${cookieAttrs(0)}`);
}

/** Carga el usuario de la sesión (si existe) en req.user. */
function loadUser(req, _res, next) {
  req.user = null;
  const token = parseCookies(req.headers.cookie)[COOKIE];
  if (token) {
    const row = db().prepare(`SELECT u.id, u.username, u.full_name, u.role, u.active, u.must_change_password,
        u.recovery_email, u.email_verified, s.expires_at, s.id AS sid
      FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.id = ?`).get(sha256(token));
    if (row && row.expires_at > new Date().toISOString() && row.active === 1) {
      req.user = row;
    } else if (row) {
      db().prepare('DELETE FROM sessions WHERE id = ?').run(row.sid);
    }
  }
  next();
}

function requireAuth(req, _res, next) {
  if (!req.user) return next(new HttpError(401, 'Su sesión no está activa. Inicie sesión nuevamente.'));
  if (req.user.must_change_password && !req.allowPendingPassword) {
    return next(new HttpError(403, 'Debe cambiar su contraseña temporal antes de continuar.', { code: 'MUST_CHANGE_PASSWORD' }));
  }
  next();
}

function requireRole(role) {
  return (req, _res, next) => {
    if (!req.user) return next(new HttpError(401, 'Su sesión no está activa.'));
    if (req.user.role !== role) return next(new HttpError(403, 'No tiene permiso para esta acción.'));
    next();
  };
}

/** Protección CSRF: las peticiones que modifican datos deben llevar un encabezado propio
 *  (los formularios de otros sitios no pueden enviarlo sin autorización CORS). */
function csrfGuard(req, _res, next) {
  if (['GET', 'HEAD', 'OPTIONS'].includes(req.method)) return next();
  if (req.get('x-requested-with') !== 'cyd-app') return next(new HttpError(403, 'Petición no permitida.'));
  next();
}

// ---- Límite de intentos de inicio de sesión (en memoria) ----
const attempts = new Map();
const MAX_ATTEMPTS = 5, LOCK_MS = 10 * 60 * 1000;
function loginBlocked(key) {
  const a = attempts.get(key);
  return a && a.until && a.until > Date.now() ? Math.ceil((a.until - Date.now()) / 60000) : 0;
}
function loginFailed(key) {
  const a = attempts.get(key) || { count: 0 };
  a.count += 1;
  if (a.count >= MAX_ATTEMPTS) { a.until = Date.now() + LOCK_MS; a.count = 0; }
  a.touched = Date.now();
  attempts.set(key, a);
}
function loginOk(key) { attempts.delete(key); }
// Elimina bloqueos vencidos y contadores antiguos para que el mapa no crezca sin límite
function pruneAttempts() {
  const now = Date.now();
  for (const [k, a] of attempts) if (!a.until || a.until <= now) { if (!a.touched || now - a.touched > LOCK_MS) attempts.delete(k); }
}

module.exports = {
  createSession, destroySession, loadUser, requireAuth, requireRole, csrfGuard,
  loginBlocked, loginFailed, loginOk, pruneAttempts,
};
