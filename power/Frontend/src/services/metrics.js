import api from './api';

/**
 * El panel del operador. Sólo administrador.
 *
 * Son DOS llamadas y no una a propósito: el uso sale de MySQL y el gasto del
 * libro del AiService. Si el puente está caído, la mitad de MySQL se sigue
 * viendo — un panel que desaparece entero porque falta una de sus dos mitades
 * es peor que uno que enseña la que sí tiene.
 */

/** Embudo, retención, actividad diaria y la tabla por atleta. */
export async function getMetrics(days = 30) {
  const { data } = await api.get('/admin/metrics', { params: { days } });
  return data.data;
}

/**
 * Gasto por herramienta y por cuenta.
 *
 * Devuelve `{ available: false, error }` en vez de lanzar cuando el AiService
 * no responde, para que la vista pueda decirlo en su sitio sin tumbar el
 * resto del panel.
 */
/** Invitaciones (Beta 3.0, Database/033): cuántos entraron invitados, cuántos cuentan y quién invita. */
export async function getReferrals() {
  const { data } = await api.get('/admin/metrics/referrals');
  return data.data;
}

export async function getSpending() {
  const { data } = await api.get('/admin/metrics/spending');
  return data.data;
}

/**
 * Lo que el producto ENTREGA: semanas prescritas contra semanas entrenadas.
 *
 * Sale del expediente del AiService, no de MySQL, así que va por su propia
 * ruta y con la misma tolerancia a fallo que el gasto. Es la única pregunta
 * del panel cuya respuesta decide si el MVP se sostiene: todo lo demás mide
 * si la gente PRUEBA la app; esto mide si le sirve.
 */
export async function getDelivery() {
  const { data } = await api.get('/admin/metrics/delivery');
  return data.data;
}

/**
 * DÓNDE se cae el atleta: generó → subió al reloj → entrenó → continuó.
 *
 * La contraparte de `getDelivery`. Aquella dice CUÁNTO se entregó; ésta dice
 * en qué escalón se rompe. La beta dejó 37 generaciones y 0 semanas cerradas,
 * y el panel medía el primer paso y el último, nada de los tres del medio —
 * que es donde estaría la explicación.
 *
 * La señal que lo hace legible es la CONTIGÜIDAD de ventanas: dos semanas que
 * se tocan son una continuada; dos que se solapan son una rehecha. Con eso el
 * bucle de regeneración deja de ser una sospecha y pasa a ser un número.
 *
 * Misma tolerancia que las otras dos del AiService: devuelve
 * `{ available: false, error }` en vez de lanzar.
 */
export async function getLoopFunnel() {
  const { data } = await api.get('/admin/metrics/loop');
  return data.data;
}

// --- La salud de la app (Beta 3.0, F1) --------------------------------------
// Lo que antes sólo se veía por SSH. Cada llamada devuelve `{ available: false,
// error }` si el AiService no responde, como el gasto: el panel no se cae entero.

/** El servicio y la cola, más el final del log de errores de PHP. */
export async function getAppHealth() {
  const { data } = await api.get('/admin/metrics/health');
  return data.data;
}

/** Los trabajos más recientes (generaciones y subidas a Garmin). */
export async function getJobs(limit = 60) {
  const { data } = await api.get('/admin/metrics/jobs', { params: { limit } });
  return data.data;
}

/** El final del log de un trabajo, saneado y diciendo cuánto se recortó. */
export async function getJobLog(jobId, lines = 200) {
  const { data } = await api.get(`/admin/metrics/job-log/${encodeURIComponent(jobId)}`, { params: { lines } });
  return data.data;
}

/** Cuántas veces corrige algo cada red determinista en la ventana. */
export async function getNets(days = 30) {
  const { data } = await api.get('/admin/metrics/nets', { params: { days } });
  return data.data;
}

/** Los atletas que gastaron más de 1 USD en un día de la última semana (Beta 3.0, F2). */
export async function getSpendingAlerts() {
  const { data } = await api.get('/admin/metrics/alerts');
  return data.data;
}

/** Los errores de pantalla de los atletas de la última semana (Beta 3.0, F3). */
export async function getClientErrors() {
  const { data } = await api.get('/admin/metrics/client-errors');
  return data.data || [];
}
