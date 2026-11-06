import api from './api';
import { relojDelAtleta } from './plan';

/**
 * Pre-week check-in API (ported from PaginaLocal's services/ai/checkin.py).
 * One check-in per athlete per day; a second submission the same day replaces it.
 */

export async function getLatestCheckin() {
  const { data } = await api.get('/checkin/latest');
  return data.data; // { available, checkin, baseline }
}

/**
 * El día lo dice el navegador, no el servidor.
 *
 * `CheckinModal` decide si ya hay check-in de hoy con la fecha LOCAL del
 * atleta, y el backend lo guardaba con `date('Y-m-d')` del servidor, que no
 * está en su país. A ciertas horas los dos no hablaban del mismo día: el
 * atleta acababa de contestar y el modal se lo volvía a pedir, y la molestia
 * que declaró el domingo por la noche viajaba al plan con fecha de lunes.
 * Mismo remedio que `relojDelAtleta()` ya aplica al generar.
 */
export async function submitCheckin(payload) {
  const { data } = await api.post('/checkin', { ...payload, today: relojDelAtleta().today });
  return data.data; // { saved, checkin }
}
