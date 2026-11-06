// Reducir una foto ANTES de guardarla.
//
// La foto de perfil vive en localStorage, que da unos 5 MB para TODO el
// origen. Una foto recién hecha con un móvil son 3-5 MB, y en base64 crece un
// 33% más: guardarla tal cual llena la cuota de una sola vez y tira abajo el
// resto de preferencias del atleta.
//
// Se recorta a un cuadrado centrado y se baja a 256px, que es más del doble
// de lo que el sitio más grande donde se muestra necesita (el avatar del
// topbar son 36px, 72 en pantalla retina). El resultado ronda los 20-30 KB.

const LADO = 256;
const CALIDAD = 0.85;

/** Tipos que el navegador sabe dibujar en un canvas. HEIC de iPhone no está. */
export const TIPOS_ACEPTADOS = 'image/png,image/jpeg,image/webp,image/gif';

/** Tope de entrada. No es el tamaño final -- es lo que se acepta leer. */
export const MAX_ENTRADA_MB = 12;

/**
 * Devuelve la foto como data URL cuadrada de 256px en JPEG.
 * Lanza Error con un mensaje ya presentable si el archivo no sirve.
 */
export async function aFotoDePerfil(file) {
  if (!file) throw new Error('No se recibió ningún archivo.');
  if (!file.type.startsWith('image/')) {
    throw new Error('Ese archivo no es una imagen.');
  }
  if (file.size > MAX_ENTRADA_MB * 1024 * 1024) {
    throw new Error(`La imagen pesa más de ${MAX_ENTRADA_MB} MB. Prueba con una más pequeña.`);
  }

  const url = URL.createObjectURL(file);
  try {
    const img = await new Promise((resolve, reject) => {
      const el = new Image();
      el.onload = () => resolve(el);
      // Un HEIC o un archivo corrupto llegan hasta aquí y fallan al decodificar.
      el.onerror = () => reject(new Error('No se pudo leer la imagen. Prueba con un JPG o un PNG.'));
      el.src = url;
    });

    // Cuadrado centrado: se recorta el lado largo, no se deforma la cara.
    const lado = Math.min(img.naturalWidth, img.naturalHeight);
    const sx = (img.naturalWidth - lado) / 2;
    const sy = (img.naturalHeight - lado) / 2;

    const canvas = document.createElement('canvas');
    canvas.width = LADO;
    canvas.height = LADO;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Este navegador no puede procesar la imagen.');
    ctx.drawImage(img, sx, sy, lado, lado, 0, 0, LADO, LADO);

    return canvas.toDataURL('image/jpeg', CALIDAD);
  } finally {
    URL.revokeObjectURL(url);
  }
}
