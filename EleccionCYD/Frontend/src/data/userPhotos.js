// Igual que participantPhotos.js: las fotos de los jurados viven en el Frontend y se mapean
// por el "id" del usuario (tabla `usuarios`). No todos los jurados tienen foto todavía --
// los que no están en este mapa caen al PlaceholderLogo.
import JeimyEscobedo from '../assets/images/JeimyEscobedo.jpeg';
import EdinMorela from '../assets/images/EdinMorela.jpeg';
import GillaryTobias from '../assets/images/GillaryTobias.jpeg';
import PlaceholderLogo from '../assets/images/PlaceholderLogo.jpg';

export const USER_PHOTOS = {
  2: JeimyEscobedo, // jescobedo
  3: EdinMorela, // emorela
  4: GillaryTobias, // gtobias
};

export function getUserPhoto(id) {
  return USER_PHOTOS[id] || PlaceholderLogo;
}
