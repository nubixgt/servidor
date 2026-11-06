import api from './api';

/**
 * Avisos del administrador.
 *
 * Ojo con los dos borrados, que no son lo mismo (ver
 * Database/011_create_notifications.sql):
 *   dismissNotification -> el atleta lo quita de SU bandeja
 *   deleteNotification  -> el administrador lo retira PARA TODOS
 */

/** La bandeja de quien llama: lo que no ha descartado. */
export async function getNotifications() {
  const { data } = await api.get('/notifications');
  return data.data;
}

/** Lo quita de MI bandeja. Sigue vivo para el resto. */
export async function dismissNotification(id) {
  const { data } = await api.delete(`/notifications/${id}`);
  return data.data;
}

/** Admin: todo lo enviado, con cuántos lo han descartado ya. */
export async function getSentNotifications() {
  const { data } = await api.get('/admin/notifications');
  return data.data;
}

/** Admin: manda un aviso a todos los atletas. */
export async function sendNotification({ title, body }) {
  const { data } = await api.post('/admin/notifications', { title, body });
  return data.data;
}

/** Admin: lo retira para todos. */
export async function deleteNotification(id) {
  const { data } = await api.delete(`/admin/notifications/${id}`);
  return data.data;
}
