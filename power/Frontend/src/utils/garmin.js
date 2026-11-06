/**
 * Los estados de Garmin dichos en español, y los números con coma.
 *
 * Analytics enseñaba "AEROBIC_HIGH_SHORTAGE", "Garmin lo llama OPTIMAL" y "BALANCED"
 * tal cual los manda la API, y los números con punto ("0.9", "6.3h") en una app que
 * escribe "8,2 h" en todo lo demás (auditoría del 2026-09-23).
 *
 * Lo que no está aquí se enseña tal cual: traducir a ojo un estado que no conocemos
 * diría algo que Garmin no dijo.
 */

const ESTADOS = {
  // Balance de carga (load focus)
  AEROBIC_LOW_SHORTAGE: 'Te falta aeróbico bajo',
  AEROBIC_HIGH_SHORTAGE: 'Te falta aeróbico alto',
  ANAEROBIC_SHORTAGE: 'Te falta anaeróbico',
  AEROBIC_LOW_FOCUS: 'Enfocado en aeróbico bajo',
  AEROBIC_HIGH_FOCUS: 'Enfocado en aeróbico alto',
  ANAEROBIC_FOCUS: 'Enfocado en anaeróbico',
  BALANCED: 'Equilibrado',
  // ACWR
  LOW: 'bajo',
  OPTIMAL: 'óptimo',
  HIGH: 'alto',
  VERY_HIGH: 'muy alto',
  // HRV
  UNBALANCED: 'Desequilibrado',
  POOR: 'Bajo',
  NO_DATA: 'Sin datos',
};

export function estadoDeGarmin(valor) {
  if (valor === null || valor === undefined || valor === '') return '';
  return ESTADOS[String(valor).toUpperCase()] || String(valor);
}

/** 6.3 -> "6,3"; 11.1 -> "11,1"; 2 -> "2". `null` -> "—". */
export function num(n, decimales = 1) {
  if (n === null || n === undefined || n === '' || !Number.isFinite(Number(n))) return '—';
  const f = 10 ** decimales;
  return (Math.round(Number(n) * f) / f).toLocaleString('es-ES', { maximumFractionDigits: decimales });
}
