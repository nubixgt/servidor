/**
 * Los errores de la pantalla del atleta (Beta 3.0, F3).
 *
 * Hasta aquí un fallo del navegador moría en su consola: el atleta veía un botón que no
 * hacía nada y nosotros no veíamos nada. Ahora:
 *
 * - se guardan los ÚLTIMOS 10 en memoria (nunca en el disco) y «Pedir ayuda» los adjunta,
 *   diciéndole antes al atleta que lo hace;
 * - y se anota uno por minuto como mucho (`error_cliente`) para el panel de salud.
 *
 * Nunca lanza ni se llama a sí mismo: un fallo al anotar un fallo se calla.
 */
import api from '../services/api';

const MAX = 10;
const UN_MINUTO = 60 * 1000;
const errores = [];
let ultimoEnviado = 0;
let enviando = false;

const texto = (e) => {
  if (!e) return 'error sin mensaje';
  if (typeof e === 'string') return e;
  return String(e.message || e.reason?.message || e.reason || e).slice(0, 300);
};

export function anotarError(error, origen = '') {
  try {
    const entrada = {
      cuando: new Date().toISOString(),
      donde: typeof window !== 'undefined' ? window.location.pathname : '',
      mensaje: `${origen ? `[${origen}] ` : ''}${texto(error)}`.slice(0, 300),
    };
    errores.push(entrada);
    if (errores.length > MAX) errores.splice(0, errores.length - MAX);

    const ahora = Date.now();
    if (enviando || ahora - ultimoEnviado < UN_MINUTO || !localStorage.getItem('token')) return;
    ultimoEnviado = ahora;
    enviando = true;
    api.post('/telemetry/client-error', { message: entrada.mensaje, page: entrada.donde })
      .catch(() => {})
      .finally(() => { enviando = false; });
  } catch (e) {
    // Anotar un fallo nunca puede ser otro fallo.
  }
}

/** Los últimos errores, para adjuntarlos a una consulta. Una copia: nadie toca la lista. */
export function ultimosErrores() {
  return errores.map((e) => ({ ...e }));
}

/** Engancha los tres sitios por donde se escapa un error en una app de Vue. */
export function instalar(app) {
  const anterior = app.config.errorHandler;
  app.config.errorHandler = (err, instancia, info) => {
    anotarError(err, `vue ${info || ''}`.trim());
    if (anterior) anterior(err, instancia, info);
    else console.error(err);
  };
  window.addEventListener('error', (ev) => anotarError(ev.error || ev.message, 'window'));
  window.addEventListener('unhandledrejection', (ev) => anotarError(ev.reason, 'promesa'));
}
