/**
 * Tiempos de carrera: la única lectura y escritura del frontend.
 *
 * Se guardan como "HH:MM:SS", siempre con tres partes. Un texto de dos partes
 * ("26:30") es de antes de los tres campos y se lee como minutos:segundos, que
 * es lo que el formulario pedía entonces; el atleta lo ve así en los tres
 * campos y lo corrige si no era eso. Ver components/forms/TiempoHMS.vue.
 */

const entero = (v) => {
  const t = String(v ?? '').trim();
  if (t === '') return '';
  const n = Number(t);
  return Number.isInteger(n) && n >= 0 ? n : null;
};

/** "03:30:00" -> {h: 3, m: 30, s: 0}. null si el texto no es un tiempo. '' -> {h:'', m:'', s:''}. */
export function leerTiempo(texto) {
  const t = String(texto ?? '').trim();
  if (!t) return { h: '', m: '', s: '' };
  if (!/^\d{1,2}(:\d{1,2}){1,2}$/.test(t)) return null;
  const partes = t.split(':').map(Number);
  const [h, m, s] = partes.length === 3 ? partes : [0, ...partes];
  if (m > 59 || s > 59) return null;
  return { h, m, s };
}

/** {h, m, s} -> "HH:MM:SS". '' si los tres están vacíos o son cero. */
export function escribirTiempo({ h, m, s }) {
  const [hh, mm, ss] = [entero(h), entero(m), entero(s)];
  if ([hh, mm, ss].every((x) => x === '' || x === 0)) return '';
  const limpio = (x, max) => (x === '' || x === null ? 0 : Math.min(x, max));
  const par = (x) => String(x).padStart(2, '0');
  return `${par(limpio(hh, 99))}:${par(limpio(mm, 59))}:${par(limpio(ss, 59))}`;
}

/** "00:25:30" -> 1530. '' si no es un tiempo. */
export function tiempoASegundos(texto) {
  const p = leerTiempo(texto);
  if (!p || p.h === '') return '';
  return p.h * 3600 + p.m * 60 + p.s;
}
