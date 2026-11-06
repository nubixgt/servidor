import api from './api';

/**
 * El registro de lo que el atleta LEVANTÓ (Beta 2.1, Database/026).
 *
 * Es la única parte de la app donde los kilos son un dato y no una suposición: los
 * escribe él. De aquí sale la progresión de carga de su programa. (Traerlos de su reloj
 * se retiró en la Beta 3.0, F0: poco intuitivo y sin uso.)
 */

/** Lo registrado en una ventana de fechas. `available:false` = falta la migración. */
export async function registroDeFuerza({ desde = '', hasta = '' } = {}) {
  const { data } = await api.get('/strength-log', { params: { desde, hasta } });
  return data.data; // { available, logs: [...] }
}

/** Guarda (o reescribe) lo hecho en una sesión. */
export async function guardarRegistroDeFuerza(sesion) {
  const { data } = await api.post('/strength-log', sesion);
  return data.data;
}

/** Deshace el registro de una sesión. */
export async function borrarRegistroDeFuerza({ fecha, session_slot = 'unica' }) {
  const { data } = await api.delete('/strength-log', { data: { fecha, session_slot } });
  return data;
}

/**
 * Lo que hizo la última vez en cada ejercicio, por su id del programa Y por su nombre.
 *
 * Sirve para enseñárselo al lado de la serie ("la última vez: 60 kg × 8"): sin eso,
 * el atleta tiene que acordarse de memoria de lo que levantó hace una semana. Los
 * registros anteriores al 2026-09-23 no tienen id (el plan no lo llevaba) y se
 * encuentran por el nombre; léelo con `ultimaDe`.
 */
export function ultimaVezPorEjercicio(logs) {
  const out = {};
  [...(logs || [])]
    .sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
    .forEach((log) => (log.exercises || []).forEach((e) => {
      const hecho = { ...e, fecha: log.fecha };
      if (e.exercise_id) out[`id:${e.exercise_id}`] = hecho;
      if (e.name) out[`nombre:${e.name}`] = hecho;
    }));
  return out;
}

/** Lo último de ESTE ejercicio del plan: por su id si lo tiene, si no por su nombre. */
export function ultimaDe(ultimas, ejercicio) {
  if (!ultimas || !ejercicio) return null;
  return (ejercicio.id && ultimas[`id:${ejercicio.id}`]) || ultimas[`nombre:${ejercicio.name}`] || null;
}

/** "60 kg × 8 (RPE 8)" — lo que se enseña al lado del ejercicio. Vacío si no hay nada. */
export function comoLoHizo(registro) {
  if (!registro) return '';
  const partes = [];
  // "57,5 kg", no "57.5 kg": se lee en español.
  if (registro.weight_kg) partes.push(`${Number(registro.weight_kg).toLocaleString('es')} kg`);
  if (registro.sets && registro.reps) partes.push(`${registro.sets}×${registro.reps}`);
  else if (registro.reps) partes.push(String(registro.reps));
  if (registro.rpe) partes.push(`RPE ${registro.rpe}`);
  return partes.join(' · ');
}

/**
 * El historial de cada ejercicio, lo más reciente primero: [{clave, nombre, entradas}].
 * Cada entrada lleva su fecha, cómo lo hizo y si subió de kilos respecto a la anterior.
 * Es lo primero que enseñan Hevy, Strong o Fitbod, y lo que hace que registrar sirva
 * para algo que el atleta ve (2026-09-23).
 */
export function historialPorEjercicio(logs, maximo = 6) {
  const porClave = new Map();
  // Un registro sin id (anterior al 2026-09-23) va con el ejercicio que tenga su
  // nombre y sí lo lleve: si no, el mismo peso muerto saldría partido en dos.
  const idDelNombre = {};
  (logs || []).forEach((log) => (log.exercises || []).forEach((e) => {
    if (e.exercise_id && e.name) idDelNombre[e.name] = e.exercise_id;
  }));
  [...(logs || [])]
    .sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
    .forEach((log) => (log.exercises || []).forEach((e) => {
      const clave = e.exercise_id || idDelNombre[e.name] || e.name;
      if (!clave) return;
      const lista = porClave.get(clave) || { clave, nombre: e.name, entradas: [] };
      const anterior = lista.entradas[lista.entradas.length - 1];
      lista.nombre = e.name || lista.nombre;
      lista.entradas.push({
        fecha: log.fecha,
        texto: comoLoHizo(e),
        subio: Boolean(anterior && e.weight_kg && anterior.kg && e.weight_kg > anterior.kg),
        kg: e.weight_kg || null,
      });
      porClave.set(clave, lista);
    }));
  return [...porClave.values()]
    .map((x) => ({ ...x, entradas: x.entradas.slice(-maximo).reverse() }))
    .filter((x) => x.entradas.some((e) => e.texto))
    .sort((a, b) => String(b.entradas[0].fecha).localeCompare(String(a.entradas[0].fecha)));
}
