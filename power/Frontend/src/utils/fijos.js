/**
 * Sus «sí o sí» que casi no hace (decisión del responsable, 2026-10-23: «que la app le alerte, sin costo de
 * IA, cuando sea 1 de 4»). La cuenta la hace el cerebro al generar la semana, con sus actividades de Garmin
 * (PaginaLocal/services/ai/training_habits.fijos_poco_hechos), y llega en el resumen del plan.
 *
 * Fernando marcó 13 «sí o sí» sin un día de descanso y nadaba los jueves 0 de 4 semanas: cada «sí o sí» va al
 * plan en su día, así que su semana salía con 14-17 sesiones y él hacía la mitad. Aquí sólo se le enseña y se
 * le ofrece pasarlo a «Puedo»; su tabla la cambia él.
 */

/** Los de la lista que SIGUEN marcados «sí o sí» en su tabla de ahora: al cambiarlos, el aviso se va. */
export function fijosPocoHechos(summary, sportDays) {
  const resumen = typeof summary === 'string' ? leer(summary) : summary;
  const tabla = typeof sportDays === 'string' ? leer(sportDays) : sportDays;
  const lista = resumen?.fijos_poco_hechos?.lista;
  if (!Array.isArray(lista)) return [];
  return lista.filter((f) => f && (tabla?.[f.deporte] || {})[f.dia] === 'fijo');
}

function leer(texto) {
  try { return JSON.parse(texto); } catch (e) { return null; }
}

/** «nadar los jueves (0 de 4 semanas)». */
export function fraseDelFijo(f) {
  const dia = String(f.dia || '').toLowerCase();
  const dias = dia.endsWith('s') ? dia : `${dia}s`;
  return `${String(f.deporte || '').toLowerCase()} los ${dias} (${f.veces} de ${f.semanas} semanas)`;
}

/** Para recordar «Mantenerlo así» en este navegador: cambia si cambia la cuenta o la lista. */
export function firmaDeFijos(summary, lista) {
  const resumen = typeof summary === 'string' ? leer(summary) : summary;
  const hasta = resumen?.fijos_poco_hechos?.hasta || '';
  return `${hasta}|${lista.map((f) => `${f.deporte}:${f.dia}`).join(',')}`;
}
