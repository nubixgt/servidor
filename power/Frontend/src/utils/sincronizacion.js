/**
 * El aviso del check-in: cuándo subió algo su reloj por última vez, dicho como lo diría una
 * persona («hoy a las 07:40», «ayer a las 21:15», «el lunes 12 a las 09:51»), o null si no hace
 * falta avisar (José, 2026-10-19: lo que no está en Garmin al generar cuenta como no hecho).
 *
 * No se avisa si sincronizó hace menos de MARGEN_HORAS: lo más probable es que ya esté todo.
 */
export const MARGEN_HORAS = 3;

const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

const hora = (d) => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
const mismoDia = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

export function cuandoSincronizo(iso, ahora = new Date()) {
  if (!iso) return null;
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return null;
  if ((ahora - d) / 3600000 < MARGEN_HORAS) return null;
  const ayer = new Date(ahora);
  ayer.setDate(ayer.getDate() - 1);
  if (mismoDia(d, ahora)) return `hoy a las ${hora(d)}`;
  if (mismoDia(d, ayer)) return `ayer a las ${hora(d)}`;
  return `el ${DIAS[d.getDay()]} ${d.getDate()} a las ${hora(d)}`;
}

/** Lo mismo pero siempre, también si fue hace un rato (para la línea de los ✓/✗, 2026-10-21). */
export function cuandoFue(iso, ahora = new Date()) {
  if (!iso) return null;
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return null;
  const ayer = new Date(ahora);
  ayer.setDate(ayer.getDate() - 1);
  if (mismoDia(d, ahora)) return `hoy a las ${hora(d)}`;
  if (mismoDia(d, ayer)) return `ayer a las ${hora(d)}`;
  return `el ${DIAS[d.getDay()]} ${d.getDate()} a las ${hora(d)}`;
}
