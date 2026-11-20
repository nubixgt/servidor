const LADO_MAX = 1600;
const UMBRAL_BYTES = 900 * 1024;

export const TIPOS_ACEPTADOS = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Las fotos de celular pesan varios MB: se reducen en el navegador antes de subirlas
 * (lado mayor 1600 px, JPEG calidad 0.82). Las imágenes pequeñas se envían tal cual.
 */
export const prepararImagen = async (archivo) => {
    if (archivo.size <= UMBRAL_BYTES) return archivo;

    try {
        const bitmap = await createImageBitmap(archivo);
        const escala = Math.min(1, LADO_MAX / Math.max(bitmap.width, bitmap.height));
        const lienzo = document.createElement('canvas');
        lienzo.width = Math.round(bitmap.width * escala);
        lienzo.height = Math.round(bitmap.height * escala);
        const ctx = lienzo.getContext('2d');
        ctx.fillStyle = '#ffffff'; // los PNG con transparencia no deben quedar negros en JPEG
        ctx.fillRect(0, 0, lienzo.width, lienzo.height);
        ctx.drawImage(bitmap, 0, 0, lienzo.width, lienzo.height);
        bitmap.close?.();

        const blob = await new Promise(res => lienzo.toBlob(res, 'image/jpeg', 0.82));
        if (!blob || blob.size >= archivo.size) return archivo;
        const nombre = archivo.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], nombre, { type: 'image/jpeg' });
    } catch {
        return archivo; // si el navegador no puede procesarla, que decida el servidor
    }
};
