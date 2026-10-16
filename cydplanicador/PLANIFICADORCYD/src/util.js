'use strict';
const crypto = require('crypto');

/** Normaliza un nombre para comparación: sin acentos, minúsculas, espacios colapsados. */
function nameKey(s) {
  return String(s || '')
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().replace(/\s+/g, ' ').trim();
}

/** Clave de una persona: palabras normalizadas y ordenadas, para que «Ana López» y «López Ana» coincidan. */
function personKey(s) {
  return nameKey(s).split(' ').filter(Boolean).sort().join(' ');
}

/** Nombre propio para mostrar: si llega todo en mayúsculas, se pasa a mayúscula inicial. */
function displayPersonName(s) {
  const c = cleanName(s);
  const small = new Set(['de', 'del', 'la', 'las', 'los', 'y']);
  const words = c === c.toUpperCase() ? c.toLowerCase().split(' ') : c.split(' ');
  return words.map((w, i) => (i > 0 && small.has(w.toLowerCase())) ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

/** Limpia un nombre para mostrar: espacios colapsados, sin espacios extremos. */
function cleanName(s) {
  return String(s || '').normalize('NFC').replace(/\s+/g, ' ').trim();
}

/** Nombre de visualización de un curso: si llega todo en mayúsculas o minúsculas, se capitaliza. */
function displayCourseName(s) {
  const c = cleanName(s);
  if (c && (c === c.toUpperCase() || c === c.toLowerCase())) {
    const small = new Set(['de', 'del', 'la', 'las', 'los', 'y', 'e', 'o', 'u', 'en', 'a', 'con', 'para', 'por']);
    return c.toLowerCase().split(' ').map((w, i) =>
      /^(i|ii|iii|iv|v|vi|vii|viii|ix|x)$/.test(w) ? w.toUpperCase()
        : (i > 0 && small.has(w)) ? w : w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
  }
  return c;
}

// ---- Contraseñas (scrypt, incluido en Node) ----
const SCRYPT = { N: 16384, r: 8, p: 1, keylen: 64 };
function hashPassword(pw) {
  const salt = crypto.randomBytes(16);
  const hash = crypto.scryptSync(pw, salt, SCRYPT.keylen, { N: SCRYPT.N, r: SCRYPT.r, p: SCRYPT.p });
  return `scrypt$${SCRYPT.N}$${SCRYPT.r}$${SCRYPT.p}$${salt.toString('base64')}$${hash.toString('base64')}`;
}
function verifyPassword(pw, stored) {
  try {
    const [alg, N, r, p, saltB64, hashB64] = String(stored).split('$');
    if (alg !== 'scrypt') return false;
    const expected = Buffer.from(hashB64, 'base64');
    const got = crypto.scryptSync(String(pw), Buffer.from(saltB64, 'base64'), expected.length,
      { N: +N, r: +r, p: +p });
    return crypto.timingSafeEqual(expected, got);
  } catch { return false; }
}
// Hash ficticio para igualar tiempos cuando el usuario no existe
const DUMMY_HASH = hashPassword(crypto.randomBytes(12).toString('hex'));

// La contraseña es libre (cualquier longitud y caracteres); sólo se exige que no esté vacía.
function passwordProblems(pw) {
  return String(pw || '').length ? [] : ['Escriba una contraseña.'];
}

function randomToken(bytes = 32) { return crypto.randomBytes(bytes).toString('base64url'); }
function sha256(s) { return crypto.createHash('sha256').update(s).digest('hex'); }
function uuid() { return crypto.randomUUID(); }

// ---- Fechas (YYYY-MM-DD) ----
const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;
function isDate(s) {
  if (typeof s !== 'string' || !DATE_RE.test(s)) return false;
  const d = new Date(s + 'T00:00:00Z');
  return !isNaN(d) && d.toISOString().slice(0, 10) === s;
}
function nowIso() { return new Date().toISOString(); }

/** Error con código HTTP y mensaje para el usuario. */
class HttpError extends Error {
  constructor(status, message, extra) { super(message); this.status = status; this.extra = extra; }
}

module.exports = {
  nameKey, personKey, displayPersonName, cleanName, displayCourseName, hashPassword, verifyPassword, DUMMY_HASH,
  passwordProblems, randomToken, sha256, uuid, isDate, nowIso, HttpError,
};
