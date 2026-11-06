import api from './api';

/**
 * Lo que el atleta nos escribe desde la app. Ver Backend FeedbackController.
 *
 * `context`: 'general' (botón Opinar) · 'borrar_semana' · 'borrar_plan' ·
 *   'pago' (el porqué de la cifra de «¿Cuánto pagarías?», retirada el 2026-10-20 al fijarse el precio).
 * `kind`: 'fallo' · 'sugerencia' · 'me_gusta' (sólo en 'general').
 * `reason`: el motivo cerrado del borrado, cuando lo hay.
 *
 * Devuelve true/false y NUNCA lanza: quien llama decide qué decirle al atleta
 * si no llegó (en el borrado, el plan se borra igual).
 */
export async function enviarFeedback({ message, context = 'general', kind = '', reason = '' }) {
  const page = typeof window !== 'undefined' ? window.location.pathname : '';
  try {
    await api.post('/feedback', {
      message, context, page,
      ...(kind ? { kind } : {}),
      ...(reason ? { reason } : {}),
    });
    return true;
  } catch (e) {
    return false;
  }
}

/** Sólo administrador: los últimos mensajes con quién los escribió. */
export async function getFeedback() {
  const { data } = await api.get('/admin/feedback');
  return data.data || [];
}

// --- «Pedir ayuda» (Beta 3.0, F3) ---------------------------------------------------
// Una consulta lleva su categoría, la sesión desde la que se preguntó y los últimos
// errores de la pantalla. El resto del diagnóstico lo arma el SERVIDOR con una lista
// blanca: aquí no se manda nada del perfil ni ninguna contraseña.

/** Las cinco categorías del botón «Ayuda», en el lenguaje del atleta. */
export const CATEGORIAS_DE_AYUDA = [
  { valor: 'plan', etiqueta: 'Mi plan no tiene sentido' },
  { valor: 'reloj', etiqueta: 'Mi reloj no muestra lo mismo' },
  { valor: 'app', etiqueta: 'La app falla' },
  { valor: 'datos', etiqueta: 'Mis datos o mi perfil' },
  { valor: 'otra', etiqueta: 'Otra' },
];

export const ESTADOS_DE_AYUDA = { nuevo: 'Recibida', investigando: 'La estamos mirando', resuelto: 'Resuelta' };

/** Envía una consulta. Devuelve true/false y nunca lanza (quien llama se lo dice). */
export async function pedirAyuda({ categoria, mensaje, sessionKey = '', erroresDelNavegador = [] }) {
  const page = typeof window !== 'undefined' ? window.location.pathname : '';
  try {
    await api.post('/feedback', {
      context: 'ayuda', category: categoria, message: mensaje, page,
      ...(sessionKey ? { session_key: sessionKey } : {}),
      client_errors: erroresDelNavegador,
    });
    return true;
  } catch (e) {
    return false;
  }
}

/** «Mis consultas»: `{ available, items }`. */
export async function misConsultas() {
  const { data } = await api.get('/feedback/mine');
  return data.data || { available: false, items: [] };
}

/** Sólo administrador: la cola de consultas con su diagnóstico. */
export async function getColaDeAyuda({ status = '', category = '' } = {}) {
  const { data } = await api.get('/admin/feedback', {
    params: { context: 'ayuda', ...(status ? { status } : {}), ...(category ? { category } : {}) },
  });
  return data.data || { available: false, items: [] };
}

export async function cambiarEstadoDeAyuda(id, status) {
  await api.patch(`/admin/feedback/${id}`, { status });
}

export async function responderAyuda(id, reply, status = '') {
  await api.post(`/admin/feedback/${id}/reply`, { reply, ...(status ? { status } : {}) });
}
