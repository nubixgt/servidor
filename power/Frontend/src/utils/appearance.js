// Catálogo único de fondos y ENTRENADORES elegibles desde el modal de
// Configuración (engranaje del topbar). Un solo lugar con los imports para
// que el store y los componentes no dupliquen rutas de assets.
//
// El entrenador dejó de ser un avatar decorativo: cada uno tiene NOMBRE y
// MÉTODO, y ese método es lo que cambia el system prompt del chat. El `tone`
// de aquí viaja al backend como parte del `coach_id` -- ver
// PaginaLocal/services/ai/coach_chat.py, que tiene su propio catálogo con los
// mismos ids y es el que manda sobre el prompt. Este de aquí manda sobre lo
// que se VE. Si añades un coach, tiene que estar en los dos.
import bg1 from '../assets/images/FondoPagina.jpeg';
import bg2 from '../assets/images/FondoPagina2.jpeg';
import bg3 from '../assets/images/FondoPagina3.jpeg';
import avatarJuan from '../assets/images/GifHombre.webp';
import avatarFernanda from '../assets/images/GifMujer.webp';

// Los retratos son WebP: los originales eran un gif de 11,8 MB y otro de
// 14,7 MB (720x1280, 100 fotogramas) para mostrarse a 44 px, mas dos JPG de
// 2048x2048. Se descargaban enteros en cada visita, tambien con datos moviles.
// A 288 px de ancho y calidad 72 son 727 y 825 KB, y los retratos estaticos
// 14 y 12 KB: 28,5 MB -> 1,5 MB, un 95% menos, sin diferencia visible al
// tamano en que se ven. Los originales siguen en el historial de git.
//
// El retrato de cada coach se busca POR SU NOMBRE, no se importa a pelo.
//
// Un import directo a un archivo que no existe rompe el build entero, así que
// un retrato que falta se llevaría por delante toda la aplicación. Buscándolo
// por patrón, lo peor que puede pasar es que ese coach salga con su inicial.
//
// Para añadir o cambiar un retrato basta con dejar el archivo aquí, con el
// nombre del coach y cualquiera de estas extensiones — sin tocar código:
//   Frontend/src/assets/images/Ben.jpg
//   Frontend/src/assets/images/Natasha.jpg
const IMAGENES = import.meta.glob(
  '../assets/images/*.{png,jpg,jpeg,gif,webp,avif}',
  { eager: true, import: 'default' }
);
const retrato = (nombre) => {
  const buscado = nombre.toLowerCase();
  const clave = Object.keys(IMAGENES).find(
    (k) => k.split('/').pop().replace(/\.[^.]+$/, '').toLowerCase() === buscado
  );
  return clave ? IMAGENES[clave] : null;
};

export const DEFAULT_BACKGROUND_ID = 'fondo1';

export const BACKGROUNDS = [
  { id: 'fondo1', label: 'Original', src: bg1 },
  { id: 'fondo2', label: 'Fondo 2', src: bg2 },
  { id: 'fondo3', label: 'Fondo 3', src: bg3 },
];

/**
 * Los cuatro entrenadores. `tone` es el que decide cómo responde en el chat y
 * es el mismo vocabulario que ya usaba la ficha del atleta
 * (directo | tecnico | empatico), para no inventar un tercer diccionario.
 *
 * `metodo` es lo que el propio coach dice de sí mismo al saludar, en primera
 * persona y en una línea: va dentro de "mi método es ___".
 */
export const COACHES = [
  {
    id: 'juan',
    name: 'Juan',
    tone: 'directo',
    toneLabel: 'Directo',
    metodo: 'ir al grano: te digo qué toca, por qué, y sin rodeos',
    src: avatarJuan,
  },
  {
    id: 'fernanda',
    name: 'Fernanda',
    tone: 'directo',
    toneLabel: 'Directa',
    metodo: 'ir al grano: te digo qué toca, por qué, y sin rodeos',
    src: avatarFernanda,
  },
  {
    id: 'ben',
    name: 'Ben',
    tone: 'tecnico',
    toneLabel: 'Técnico',
    metodo: 'la fisiología y los números: qué adaptación busca cada sesión y qué dicen tus tendencias',
    src: retrato('Ben'),
  },
  {
    id: 'natasha',
    name: 'Natasha',
    tone: 'empatico',
    toneLabel: 'Empática',
    metodo: 'explicártelo claro y sin agobios, porque lo que construye la forma es la constancia',
    src: retrato('Natasha'),
  },
];

/**
 * Los ids viejos ('hombre', 'mujer') son de cuando esto era sólo un avatar.
 * Quien ya había elegido uno se queda con el mismo personaje en vez de
 * perder la preferencia al actualizar.
 */
const IDS_ANTIGUOS = { hombre: 'juan', mujer: 'fernanda' };

export const normalizeCoachId = (id) => {
  if (!id) return null;
  const migrado = IDS_ANTIGUOS[id] || id;
  return COACHES.some((c) => c.id === migrado) ? migrado : null;
};

/** El coach elegido, o `null` si todavía no ha elegido ninguno. */
export const findCoach = (id) => COACHES.find((c) => c.id === normalizeCoachId(id)) || null;
