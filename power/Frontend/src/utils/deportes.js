/**
 * Cómo se llama cada deporte y cada disciplina en pantalla. Una sola copia.
 *
 * `discipline` es una lista cerrada del cerebro (`plan_outputs.Discipline`) escrita
 * sin tildes, y el plan la enseñaba tal cual: "NATACION", "BRICK" (auditoría del
 * 2026-09-23). El panel y el historial tenían cada uno su propia tabla de deportes.
 */

export const SPORT_ES = {
  run: 'Carrera', bike: 'Bici', swim: 'Natación', strength: 'Fuerza', walk: 'Caminata',
  brick: 'Combinado', trail: 'Trail', rest: 'Descanso',
};

export const DISCIPLINA_ES = {
  carrera: 'Carrera', trail: 'Trail', bici: 'Bici', natacion: 'Natación', fuerza: 'Fuerza',
  pliometria: 'Pliometría', rehab: 'Rehabilitación', prehab: 'Prevención', movilidad: 'Movilidad',
  tecnica: 'Técnica', brick: 'Carrera tras la bici', test: 'Test', descanso: 'Descanso',
};

/** Lo que la sesión ES (su disciplina) y, si no la trae, su deporte. */
export function etiquetaDeLaSesion(day) {
  const d = day?.discipline;
  if (d && d !== 'otro' && DISCIPLINA_ES[d]) return DISCIPLINA_ES[d];
  return SPORT_ES[day?.sport] || day?.sport || '';
}

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sept', 'oct', 'nov', 'dic'];

/** "2026-10-05" -> "5 oct". Una fecha que no es ISO se devuelve tal cual. */
export function fechaCorta(iso) {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso || '');
  if (!m) return iso || '';
  return `${Number(m[3])} ${MESES[Number(m[2]) - 1]}`;
}

/**
 * Icono (Material Symbols) y color de cada deporte en el calendario y al mover una sesión. El color es
 * para leer la semana de un vistazo, no el acento de la app; la carrera (competición) va aparte.
 */
export const ICONO_DEL_DEPORTE = {
  run: 'directions_run', trail: 'hiking', bike: 'directions_bike', swim: 'pool', strength: 'fitness_center',
  walk: 'directions_walk', brick: 'sync_alt', race: 'emoji_events', rest: 'bedtime',
};
export const COLOR_DEL_DEPORTE = {
  run: '#caf300', trail: '#86e0b5', bike: '#ffb38a', swim: '#a5eeff', strength: '#d7b8ff', walk: '#c5c9ac',
  brick: '#ff8f83', race: '#ffd166', rest: '#8f9378',
};
/** El deporte con el que se dibuja una sesión: la competición es «race» aunque se corra. */
export const deporteVisible = (day) => (day?.intensity_class === 'race_day' ? 'race' : (day?.is_rest ? 'rest' : day?.sport));
