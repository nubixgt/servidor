/**
 * La imagen de referencia de un ejercicio.
 *
 * ## De dónde sale la tabla
 *
 * De `public/ejercicios/catalogo.json`, que NO se escribe a mano: lo genera
 * `PaginaLocal/scripts/generar_catalogo_frontend.py` a partir de
 * `services/ai/exercise_catalog.py`, que es la misma lista cerrada que el
 * prompt le ofrece al modelo para elegir `video_key`. Una sola fuente, dos
 * copias generadas — porque dos listas escritas a mano son dos listas que
 * divergen, y este proyecto ya tiene el ejemplo: el desplegable de
 * competiciones ofrece cinco valores que el catálogo de Python no reconoce.
 *
 * ## La regla: sin imagen antes que con la imagen equivocada
 *
 * `resolver()` empareja EXACTO. Si el nombre no está en el catálogo ni entre
 * sus alias, devuelve `null` y la tarjeta se pinta como se pintaba antes.
 * Enseñarle al atleta el dibujo de otro ejercicio le haría hacer el movimiento
 * equivocado creyendo que hace el suyo.
 *
 * Los alias incluyen a propósito las malas traducciones que el modelo produce
 * ("bicho muerto" por dead bug, "perro pájaro" por bird dog): la imagen que
 * sale sigue siendo la correcta.
 *
 * ## Por qué se carga aparte y no se importa
 *
 * Son 29 KB que sólo hacen falta si el atleta abre una sesión de fuerza. Un
 * `import` los metería en el bundle de todo el mundo, incluido el que sólo
 * mira su semana de carrera.
 */

// La app vive bajo /power/ (vite `base`). Con `?.` el módulo también carga en node plano (la prueba del plural).
const BASE = import.meta.env?.BASE_URL ?? '/';
const RUTA_CATALOGO = `${BASE}ejercicios/catalogo.json`;
const RUTA_IMAGENES = `${BASE}ejercicios/`;

let promesa = null;
let catalogo = null;

/**
 * La misma normalización que `exercise_catalog.normalizar` en Python.
 *
 * Si las dos dejan de coincidir, un ejercicio resuelve en el servidor y no en
 * el navegador (o al revés) sin que nadie se entere. El apóstrofo se BORRA en
 * vez de convertirse en separador: "farmer's carry" y "farmers carry" son el
 * mismo ejercicio y el modelo escribe los dos.
 */
export function normalizar(texto) {
  if (!texto) return '';
  return String(texto)
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/['‘’`´]/g, '')
    .replace(/[^a-zA-Z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .toLowerCase();
}

/**
 * Carga el catálogo una sola vez. Si falla, se resuelve a null y la app sigue
 * funcionando sin imágenes: nunca es motivo para romper la vista del plan.
 */
export function cargarCatalogo() {
  if (promesa) return promesa;
  promesa = fetch(RUTA_CATALOGO)
    .then((r) => (r.ok ? r.json() : null))
    .then((d) => {
      catalogo = d && d.ejercicios ? d : null;
      return catalogo;
    })
    .catch(() => {
      catalogo = null;
      return null;
    });
  return promesa;
}

/**
 * El ejercicio del catálogo, o null si no se puede afirmar cuál es.
 *
 * @param {string} nombre     lo que el plan muestra ("Sentadilla búlgara")
 * @param {string} videoKey   la clave que eligió el modelo ("sentadilla_bulgara")
 * @returns {{slug:string, es:string, en:string, grupo:string, img:string|null}|null}
 */
export function resolver(nombre, videoKey) {
  if (!catalogo) return null;
  for (const candidato of [videoKey, nombre]) {
    const clave = normalizar(candidato);
    if (!clave) continue;
    for (const forma of [clave, enSingular(clave), enSingular(clave, true)]) {
      const directo = catalogo.ejercicios[forma];
      if (directo) return { slug: forma, ...directo };
      const destino = catalogo.alias[forma];
      if (destino && catalogo.ejercicios[destino]) {
        return { slug: destino, ...catalogo.ejercicios[destino] };
      }
    }
  }
  return null;
}

/**
 * La clave con sus palabras en singular ("flexiones" -> "flexion"), igual que
 * `exercise_catalog._en_singular` en Python: la gente escribe su rutina en plural
 * y los alias están en singular. Sólo plurales regulares; se sigue comparando exacto.
 */
export function enSingular(clave, soloLaPrimera = false) {
  const singular = (p) => {
    if (p.endsWith('iones')) return p.slice(0, -5) + 'ion';
    if (p.endsWith('ces') && p.length > 4) return p.slice(0, -3) + 'z';
    if (p.endsWith('s') && p.length > 3 && !p.endsWith('ss')) return p.slice(0, -1);
    return p;
  };
  const palabras = clave.split('_');
  if (soloLaPrimera) return [singular(palabras[0]), ...palabras.slice(1)].join('_');
  return palabras.map(singular).join('_');
}

/** La URL de la imagen de un ejercicio, o null si no hay ninguna que enseñar. */
export function imagenDe(nombre, videoKey) {
  const e = resolver(nombre, videoKey);
  return e && e.img ? RUTA_IMAGENES + e.img : null;
}
