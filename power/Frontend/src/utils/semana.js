/**
 * Las fechas de la semana que se va a generar: las mismas que calcula el
 * servidor (`PaginaLocal/web_app/server.py::_next_week_start` y
 * `dias_del_bloque`).
 *
 * El check-in enseñaba los 9 días desde hoy. La semana siguiente de Jessie
 * empieza el lunes 21 y el check-in del 19 le habría enseñado el 19 y el 20, que
 * ya tienen plan: un día marcado ahí no hacía nada, y nadie se enteraba. Las
 * constantes valen lo mismo en los dos lados (lo fija
 * `PaginaLocal/tests/test_checkin_de_cada_generacion.py`).
 */
import { WEEK_DAYS } from './profile';

/** Un primer bloque de uno o dos días se une a la semana siguiente. */
export const MIN_DIAS_DEL_BLOQUE = 3;
/** Una última fecha más allá de esto es un dato roto: se arranca hoy. */
export const MAX_DIAS_ADELANTE_PLAUSIBLES = 90;

const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const leer = (texto) => {
  const [y, m, d] = String(texto).split('-').map(Number);
  return new Date(y, m - 1, d, 12);
};

const esFecha = (texto) => /^\d{4}-\d{2}-\d{2}$/.test(String(texto || ''));

/** El día siguiente al último del plan, o hoy si no hay plan o se quedó atrás. */
export function inicioDeLaProximaSemana(weeklyPlan, hoy = new Date()) {
  const base = new Date(hoy);
  base.setHours(12, 0, 0, 0);
  const fechas = ((weeklyPlan && weeklyPlan.weeks) || [])
    .flatMap((w) => (w.days || []).map((d) => d.date))
    .filter(esFecha)
    .sort();
  if (!fechas.length) return base;
  const ultima = leer(fechas[fechas.length - 1]);
  if (Math.round((ultima - base) / 86400000) > MAX_DIAS_ADELANTE_PLAUSIBLES) return base;
  const siguiente = new Date(ultima);
  siguiente.setDate(ultima.getDate() + 1);
  return siguiente > base ? siguiente : base;
}

/** Cuántos días escribe la generación: hasta la víspera del día en que empieza su semana. */
export function diasDelBloque(inicio, weekStartDay) {
  const i = WEEK_DAYS.indexOf(weekStartDay);
  if (i < 0) return 7;
  const hasta = (((i - (inicio.getDay() + 6) % 7) % 7) + 7) % 7 || 7;
  return hasta >= MIN_DIAS_DEL_BLOQUE ? hasta : hasta + 7;
}

/**
 * Las fechas ISO de la semana que se va a generar. `arranque` es el "hoy o
 * mañana" que se elige sólo en la PRIMERA semana; con plan se ignora.
 */
export function fechasDeLaProximaSemana(weeklyPlan, weekStartDay, { hoy = new Date(), arranque = '' } = {}) {
  const tienePlan = ((weeklyPlan && weeklyPlan.weeks) || []).length > 0;
  const inicio = arranque && !tienePlan && esFecha(arranque) ? leer(arranque) : inicioDeLaProximaSemana(weeklyPlan, hoy);
  return Array.from({ length: diasDelBloque(inicio, weekStartDay) }, (_, k) => {
    const d = new Date(inicio);
    d.setDate(inicio.getDate() + k);
    return iso(d);
  });
}
