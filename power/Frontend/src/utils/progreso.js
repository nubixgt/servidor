/**
 * Qué estadísticas ve el atleta (Beta 3.0, F4; decisión del responsable del 2026-10-10:
 * «quitarlas al atleta, pero dejar las más famosas como VO2max»).
 *
 * El ACWR, el Load Focus, la VFC, el estrés, la disponibilidad y el sueño de Garmin son
 * de LECTURA, no de acción: nadie decide nada con un 1,15 sin saber qué es un ACWR, y un
 * número que no se entiende se lee como un aviso. Siguen calculándose igual
 * (`services/ai/charts.py` no cambia) y la IA los sigue usando todos; el administrador los
 * ve con «Vista técnica».
 *
 * El peso, el pulso en reposo y las horas de sueño tampoco (decisión del responsable del
 * 2026-10-11): el peso salió de esta lista ese día.
 */
export const TARJETAS_DEL_ATLETA = [
  // Sin «Cómo se reparte tu semana» (2026-10-21): repetía «por deporte» en otra unidad y sin decir qué semana.
  'race_goal', 'pvr', 'quality', 'weekday', 'volume', 'fitness',
];

const CLAVE_VISTA_TECNICA = 'pc.vistaTecnica';

/** Si el administrador pidió ver también las técnicas, en ESTE navegador. */
export function vistaTecnicaGuardada() {
  try { return localStorage.getItem(CLAVE_VISTA_TECNICA) === '1'; } catch (e) { return false; }
}

export function guardarVistaTecnica(valor) {
  try { localStorage.setItem(CLAVE_VISTA_TECNICA, valor ? '1' : '0'); } catch (e) { /* no recordarlo no impide verlo */ }
}
