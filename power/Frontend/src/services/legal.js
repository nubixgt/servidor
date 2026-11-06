import api from './api';

/**
 * Las condiciones de uso, y la constancia de haberlas aceptado.
 *
 * El texto vive en el backend (`Backend/src/Support/Legal.php`) y no aquí a
 * propósito: tiene que cambiar a la vez que su versión, y la versión es lo que
 * decide a quién se le vuelve a pedir. Dos copias serían dos textos que
 * divergen, y el atleta acabaría aceptando uno distinto del que queda sellado.
 */

/** El documento vigente: `{ version, markdown }`. */
export async function getLegal() {
  const { data } = await api.get('/legal');
  return data.data;
}

/**
 * Sella la aceptación de ESA versión.
 *
 * El backend la comprueba contra la suya y responde 409 si no coinciden — una
 * pestaña abierta desde ayer estaría aceptando un texto que ya no está en pie.
 */
export async function acceptLegal(version) {
  // `confirma`: la casilla de 18 años y del aviso de salud. El backend también
  // la exige; sólo se llama desde LegalGate con la casilla marcada.
  const { data } = await api.post('/legal/accept', { version, confirma: true });
  return data.data;
}
