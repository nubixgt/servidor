import api from './api';

/**
 * Training plan (macrocycle + weekly). Generation runs in the background on
 * the AiService bridge; the client polls getStatus() until done.
 */

export async function getPlan() {
  const { data } = await api.get('/plan');
  return data.data;
}

/**
 * El reloj del ATLETA, que es el único que importa.
 *
 * El servidor no está en su país. A las 8 de la tarde en Guatemala ya es el día
 * siguiente en UTC, así que la app le escribía una semana que empezaba mañana
 * cuando él veía hoy, y el calendario le decía que entrenara un día que para él
 * ya había pasado. El navegador es el único que sabe qué día es para quien está
 * mirando la pantalla, así que lo dice él.
 *
 * `timeZone` es IANA ("America/Guatemala"). `today` se compone a mano en vez de
 * con toISOString(), que convierte a UTC y devolvería justo el día equivocado.
 */
export function relojDelAtleta() {
  const ahora = new Date();
  const dd = (n) => String(n).padStart(2, '0');
  let timezone = '';
  try {
    timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
  } catch (e) {
    timezone = '';
  }
  return {
    timezone,
    today: `${ahora.getFullYear()}-${dd(ahora.getMonth() + 1)}-${dd(ahora.getDate())}`,
  };
}

export async function startSeason(garminPassword, adjustment = '') {
  const { data } = await api.post('/plan/generate-season', {
    garmin_password: garminPassword,
    adjustment,
    ...relojDelAtleta(),
  });
  return data.data;
}

/**
 * Lanza la generacion de la semana. Puede fallar con 409 si todavia no toca
 * (ventana de antelacion de 2 dias): ese caso -- y solo ese -- se puede
 * reintentar con force = true.
 *
 * `startDate` (YYYY-MM-DD) sólo se atiende en la PRIMERA semana, cuando no hay
 * una anterior de la que continuar. Después, la siguiente empieza donde acabó
 * la última y no hay nada que elegir.
 */
export async function startWeek(garminPassword, redo = false, force = false, adjustment = '', startDate = '', skipCheckin = false) {
  const { data } = await api.post('/plan/generate-week', {
    garmin_password: garminPassword,
    redo,
    force,
    adjustment,
    start_date: startDate,
    // "Saltar" en el check-in: la semana no usa ninguno, tampoco uno de hace un rato.
    skip_checkin: skipCheckin,
    ...relojDelAtleta(),
  });
  return data.data;
}

/**
 * Lo que le queda (D2, Beta 3.0 F2): cuántas versiones de su semana actual y desde
 * cuándo otro macrociclo. `{ available: false }` si el servidor no tiene la 028.
 * Nunca en dinero.
 */
/**
 * Hasta cuándo puede generar y el precio (Q60 / 8 USD al mes): `estado` es prueba, pagado, vencido,
 * sin_empezar, garmin_usado, admin o sin_muro. Ver Backend `AccessService`.
 */
/** ✓/✗ de cada sesión reciente, desde su Garmin y sin IA (2026-10-21). `forzar`: «Actualizar». */
export async function getEstadoDeLasSesiones(forzar = false) {
  const { today } = relojDelAtleta();
  const { data } = await api.get('/plan/session-status', { params: { today, ...(forzar ? { force: 1 } : {}) }, timeout: 70000 });
  return data.data; // { sesiones: {"fecha|franja": {estado, minutos, km, ...}}, ultima_sincronizacion, error?, motivo? }
}

export async function getAcceso() {
  const { data } = await api.get('/plan/access');
  return data.data;
}

export async function getUltimaSincronizacion() {
  // Cuándo subió algo su reloj por última vez (ISO o null): el aviso del check-in.
  const { data } = await api.get('/plan/garmin-sync');
  return data.data?.ultima_sincronizacion || null;
}

export async function getGenerationQuota() {
  const { data } = await api.get('/plan/generation-quota');
  return data.data;
}

/**
 * Las respuestas a las preguntas que el coach hace a mitad de la generacion,
 * una por pregunta y en el mismo orden ('' = no quiero contestar esa).
 */
export async function sendCoachAnswers(jobId, answers, questions = []) {
  // `questions` viaja para que el backend anote cada respuesta en el cuaderno:
  // sin eso el coach volvia a preguntar lo mismo en la siguiente generacion.
  const { data } = await api.post('/plan/coach-answers', { job_id: jobId, answers, questions });
  return data.data;
}

export async function getStatus() {
  const { data } = await api.get('/plan/generate-status');
  return data.data; // { kind, running, progress, status_text, done, error, plan? }
}

/**
 * Borra el plan. Con la contraseña de Garmin, lo saca también del reloj.
 *
 * La contraseña es opcional: si el atleta tiene una guardada se usa esa, y si
 * no, la escribe en ese momento. Sin ninguna, el plan se borra aquí y las
 * sesiones se quedan en su Garmin.
 *
 * Devuelve `{ garmin: { retirados, pendientes, error } }` para poder decirle
 * qué pasó con su reloj — un borrado silencioso a medias es peor que uno que
 * falla en voz alta.
 *
 * `retirarDeGarmin` es explícito a propósito. Con una contraseña guardada, no
 * mandar ninguna significa "usa la mía"; sin ella, significa "no toques mi
 * Garmin". Deducirlo de si el campo viene vacío confundiría las dos cosas.
 */
export async function resetPlan(
  garminPassword = '', scope = 'all', retirarDeGarmin = undefined, motivo = '', notaMotivo = '',
) {
  const { data } = await api.delete('/plan', {
    data: {
      scope,
      retire_from_garmin: retirarDeGarmin === undefined ? !!garminPassword : !!retirarDeGarmin,
      ...(garminPassword ? { garmin_password: garminPassword } : {}),
      // POR QUE lo borra. Opcional a proposito: el backend lo anota si viene y
      // borra igual si no. Una encuesta no puede impedirle borrar su plan.
      ...(motivo ? { reason: motivo } : {}),
      ...(notaMotivo ? { reason_note: notaMotivo } : {}),
    },
  });
  return data;
}

export async function getPlanHistory() {
  const { data } = await api.get('/plan/history');
  return data.data.entries || [];
}

export async function getPlanHistoryEntry(id) {
  const { data } = await api.get(`/plan/history/${id}`);
  return data.data;
}

export async function deletePlanHistoryEntry(id) {
  const { data } = await api.delete(`/plan/history/${id}`);
  return data;
}

/** type = 'analysis' | 'planning' → the AI-rendered HTML report, or throws 404. */
export async function getReportHtml(type) {
  const { data } = await api.get(`/plan/report/${type}`);
  return data.data.html;
}

export async function getCharts() {
  const { data } = await api.get('/plan/charts');
  return data.data; // { charts:{...}, weeks_recorded, last_refresh, stale }
}

export async function refreshCharts(garminPassword) {
  const { data } = await api.post('/plan/charts/refresh', { garmin_password: garminPassword });
  return data.data;
}

export async function startGarminUpload(garminPassword, dates = null) {
  const { data } = await api.post('/plan/garmin-upload', { garmin_password: garminPassword, dates });
  return data.data;
}

export async function getGarminUploadStatus() {
  const { data } = await api.get('/plan/garmin-upload-status');
  return data.data; // { running, progress, status, error, result }
}

/**
 * «Generar mi semana automáticamente» (Database/027). La zona horaria es la del
 * navegador: el servidor no sabe qué hora es para el atleta, y la semana se lanza la
 * víspera a las 21:00 de SU hora.
 */
export async function getAutoWeek() {
  const { data } = await api.get('/plan/auto-week');
  return data.data;
}

export async function setAutoWeek(enabled) {
  let timezone = '';
  try { timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { /* sin zona */ }
  const { data } = await api.put('/plan/auto-week', { enabled, timezone });
  return data.data;
}

/**
 * Qué lleva el reloj de cada sesión (Beta 3.0, F5): `{ sessions: { "fecha|franja":
 * "en_el_reloj" | "cambios_sin_enviar" | "sin_subir" } }`. Nunca lanza.
 */
export async function getGarminSessions() {
  try {
    const { data } = await api.get('/plan/garmin-sessions');
    return data.data?.sessions || {};
  } catch (e) {
    return {};
  }
}

/**
 * Mover una sesión dentro de su semana (Beta 3.0, F6). Con `dryRun` sólo dice lo que
 * pasaría (resumen y avisos). Lanza con `response.data.message` si no se puede.
 */
export async function moverSesion({ sessionKey, toDate, modo = 'intercambiar', planVersion, dryRun = false }) {
  return editarSesion({ op: 'mover', sessionKey, toDate, modo, planVersion, dryRun });
}

/**
 * Cambiar una sesión (Beta 3.0, F6-F7): `op` mover | editor | ajustar | revertir. `editor`
 * devuelve sus elementos y las plantillas; `ajustar` recibe `elementos` (o `grupos` en la fuerza).
 * 2026-10-23: `nuevo` (con qué empieza un entreno del atleta), `anadir` (con `toDate`, `deporte` y
 * `titulo`) y `quitar` (sólo los que añadió él).
 */
export async function editarSesion({ op, sessionKey = '', toDate = '', modo = 'intercambiar', planVersion, dryRun = false,
  elementos = null, grupos = null, deporte = '', titulo = '' }) {
  const { data } = await api.patch('/plan/session', {
    op, session_key: sessionKey, to_date: toDate, modo, plan_version: planVersion,
    dry_run: dryRun, ...(elementos ? { elementos } : {}), ...(grupos ? { grupos } : {}),
    ...(deporte ? { deporte } : {}), ...(titulo ? { titulo } : {}), ...relojDelAtleta(),
  });
  return data.data;
}
