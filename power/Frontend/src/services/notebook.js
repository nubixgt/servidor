import api from './api';

/**
 * Coach's notebook API (ported from PaginaLocal's services/ai/notebook.py).
 * Entries are per authenticated user.
 */

export async function getNotebook() {
  const { data } = await api.get('/notebook');
  return data.data; // { entries, categories, counts }
}

export async function addNote(payload) {
  const { data } = await api.post('/notebook', payload); // { text, category, status? }
  return data.data;
}

export async function patchNote(id, payload) {
  const { data } = await api.patch(`/notebook/${id}`, payload); // { text?, category?, status?, retired_reason? }
  return data.data;
}

/** Retira la anotación (no se borra). `paraSiempre` borra de verdad una que ya está retirada. */
export async function deleteNote(id, paraSiempre = false) {
  const { data } = await api.delete(`/notebook/${id}`, { params: paraSiempre ? { para_siempre: 1 } : {} });
  return data;
}

/**
 * «Reordenar cuaderno» (Beta 3.0, F9): la PROPUESTA, ya comprobada, sin guardar nada.
 * Usa un modelo barato (el costo lo ve sólo el administrador).
 */
export async function reorganizarCuaderno(respuestas = []) {
  const { data } = await api.post('/notebook/reorganize', respuestas.length ? { respuestas } : {});
  return data.data; // { preguntas, acciones, avisos, descartadas, aviso }
}

/** Acepta UNA acción de la propuesta. Nada se borra: la original se retira con su motivo. */
export async function aplicarReorganizacion(accion) {
  const { data } = await api.post('/notebook/reorganize/apply', accion);
  return data.data;
}
