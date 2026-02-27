// Script para construir URLs absolutas de archivos
function construirURLArchivo(rutaArchivo) {
    // Obtener el origen del servidor (http://159.65.168.91)
    const origen = window.location.origin;
    // Construir URL completa
    return `${origen}/Oirsa/${rutaArchivo}`;
}
