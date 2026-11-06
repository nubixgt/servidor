import api from './api';

/**
 * Athlete profile API (ported from PaginaLocal's UserProfileModel).
 * The backend keys the profile by the authenticated user, so no id is needed.
 */

export async function getProfile() {
  const { data } = await api.get('/profile');
  return data.data;
}

export async function saveProfile(payload) {
  const { data } = await api.put('/profile', payload);
  return data.data;
}

export async function resetProfile() {
  const { data } = await api.delete('/profile');
  return data;
}

/**
 * Auto-fill from Garmin (via the AiService bridge). Returns
 * { imported: {...}, fields: [...], suggested_long_days, suggested_available_days }.
 * Persists nothing — the caller merges the fields into the form.
 */
export async function garminImport({ garmin_email, garmin_password }) {
  const { data } = await api.post('/profile/garmin-import', { garmin_email, garmin_password });
  return data.data;
}

/**
 * El test de umbral en el reloj del atleta (sin IA).
 *   action "subir":    lo programa en su Garmin para `fecha` -> { programado, nombre, ... }
 *   action "analizar": lee la actividad hecha con ESE entreno -> { encontrada, lthr, ritmo, ... }
 * No guarda nada en el perfil: el atleta elige qué usar y se guarda con Guardar.
 */
export async function testDeUmbral({ sport, action, fecha = '', garmin_password = '' }) {
  const { data } = await api.post('/profile/threshold-test', { sport, action, fecha, garmin_password }, { timeout: 100000 });
  return data.data;
}

/**
 * Ordena la rutina de gimnasio pegada (Beta 2.1, S4): una llamada barata a la IA
 * que la lee, y comprobaciones en código que la convierten en dudas visibles.
 * Devuelve el borrador { sessions, unread_lines, doubts, ... }. No guarda nada:
 * la rutina se guarda con el perfil cuando el atleta la confirma.
 */
export async function ordenarRutina({ text, grouping, rest_between_rounds_s }) {
  const { data } = await api.post('/profile/strength-routine', { text, grouping, rest_between_rounds_s }, { timeout: 95000 });
  return data.data;
}

/**
 * El coach escribe el programa de gimnasio (Beta 2.1, S6), una vez por bloque.
 * Devuelve el mismo borrador que una rutina pegada, para revisarlo y confirmarlo.
 */
export async function programaDelCoach(perfilDeFuerza) {
  // Lo elegido en pantalla aunque no esté guardado: se guarda al confirmar el programa.
  const { data } = await api.post('/profile/strength-program',
    perfilDeFuerza ? { strength_profile: perfilDeFuerza } : {}, { timeout: 185000 });
  return data.data;
}

/**
 * La contraseña de Garmin, guardada cifrada.
 *
 * El backend NO puede leerla: la cifra el AiService, que es el único con la
 * clave, y en MySQL sólo queda un bloque opaco. Nunca se devuelve al navegador
 * — sólo si existe o no.
 *
 * Mandar "" la borra, y el atleta vuelve a escribirla cada vez.
 */
export async function saveGarminPassword(garminPassword) {
  const { data } = await api.post('/profile/garmin-password', { garmin_password: garminPassword });
  return data.data; // { saved: boolean }
}

export async function getGarminPasswordStatus() {
  const { data } = await api.get('/profile/garmin-password');
  return data.data; // { saved: boolean }
}
