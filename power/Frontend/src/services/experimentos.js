import api from './api';

/**
 * Las puertas falsas: entradas visibles a cosas que todavía no existen.
 *
 * Compran información sobre demanda al precio de un botón, en vez de al precio
 * de una función. La beta dejó una lista de peticiones —app nativa, otros
 * relojes, vídeos, nutrición— y construirlas todas para descubrir cuál
 * importaba costaría meses.
 *
 * Con cinco atletas ninguna dará significancia estadística. Dará algo más útil
 * a esta escala: **a quién le importó lo bastante como para tocarla**, para
 * poder preguntarle a esa persona en concreto.
 *
 * La lista de puertas válidas vive en el BACKEND
 * (`ExperimentController::PUERTAS`) y es cerrada: una puerta que no esté ahí se
 * rechaza con 400. Es deliberado —si el cliente pudiera inventar nombres, el
 * panel agruparía por cadenas que no coinciden, que es exactamente cómo una
 * medición deja de poder sumarse.
 */

/**
 * Anota que alguien tocó una puerta.
 *
 * **Nunca lanza.** Es un experimento, no una función: si la anotación falla, el
 * atleta ve la misma pantalla honesta de "todavía no existe" y no se entera de
 * nada. Que se pierda un dato de medición no puede estropearle la sesión.
 *
 * @param {string} puerta    clave de `ExperimentController::PUERTAS`
 * @param {string} respuesta opcional, para las puertas que además preguntan algo
 */
export async function tocarPuerta(puerta, respuesta = '', etapa = 'confirmada') {
  try {
    await api.post('/puerta', {
      door: puerta,
      ...(etapa !== 'confirmada' ? { stage: etapa } : {}),
      ...(respuesta ? { answer: respuesta } : {}),
    });
    return true;
  } catch (e) {
    return false;
  }
}

/**
 * Anota que el atleta VIO la puerta. Una vez por sesión del navegador y puerta.
 *
 * Sin esto, "3 toques" no dice nada: tres de tres que la vieron es demanda, tres
 * de cuarenta es ruido. La conversión de una puerta falsa es toques ÷ vistas, y
 * hasta aquí sólo se medía el numerador.
 */
export async function verPuerta(puerta) {
  const clave = `puerta_vista:${puerta}`;
  try {
    if (sessionStorage.getItem(clave)) return false;
    sessionStorage.setItem(clave, '1');
  } catch (e) {
    // Sin sessionStorage (modo privado estricto) se anota igual: mejor una
    // vista de más que perder la medición entera.
  }
  return tocarPuerta(puerta, '', 'vista');
}

/**
 * Anota que el atleta abrió una pantalla (Beta 3.0, F0). Nunca lanza, y la misma
 * pantalla no se repite antes de 30 s: volver atrás y adelante no son dos usos.
 * Sólo las del atleta: es la misma lista que `ExperimentController::PANTALLAS` (una
 * prueba fija que coinciden). Mandarle las del administrador daba un 400 en la consola
 * en cada pantalla de Métricas o Usuarios.
 */
export const PANTALLAS_MEDIDAS = [
  'AdminDashboard', 'AdminProfile', 'AdminPlan', 'AdminHistorial',
  'AdminCuaderno', 'AdminAnalytics', 'AdminReports', 'AdminWelcome',
];
const ultimaVista = {};
export async function verPantalla(nombre) {
  if (!PANTALLAS_MEDIDAS.includes(nombre)) return false;
  const ahora = Date.now();
  if (ultimaVista[nombre] && ahora - ultimaVista[nombre] < 30000) return false;
  ultimaVista[nombre] = ahora;
  try {
    await api.post('/telemetry/screen', { screen: nombre });
    return true;
  } catch (e) {
    return false;
  }
}
