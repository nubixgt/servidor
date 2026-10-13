<?php
namespace Core;

class Uploads {
    const MAX_BYTES = 5242880; // 5 MB
    private static $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private static function base() {
        return realpath(__DIR__ . '/../../uploads') ?: (__DIR__ . '/../../uploads');
    }

    // ¿Llegó un archivo en $_FILES[$campo]? Valida error, tamaño y contenido real (no la extensión).
    public static function has($campo) {
        return isset($_FILES[$campo]) && $_FILES[$campo]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    // Guarda la imagen en uploads/{carpeta}/{nombre}.{ext} y devuelve la ruta relativa ("uploads/...").
    public static function save($campo, $carpeta, $nombre, $etiqueta = 'imagen') {
        $f = $_FILES[$campo];
        if ($f['error'] !== UPLOAD_ERR_OK) throw new HttpException("No se pudo subir la $etiqueta.", 400, [$campo => 'Error al subir el archivo.']);
        if ($f['size'] > self::MAX_BYTES) throw new HttpException("La $etiqueta supera los 5 MB.", 400, [$campo => 'Máximo 5 MB.']);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::$tipos[$mime])) throw new HttpException("Formato de $etiqueta no permitido (usa JPG, PNG o WebP).", 400, [$campo => 'Usa JPG, PNG o WebP.']);

        $dir = self::base() . '/' . $carpeta;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new HttpException('No se pudo crear la carpeta de imágenes.', 500);

        $archivo = $nombre . '.' . self::$tipos[$mime];
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $archivo)) throw new HttpException("No se pudo guardar la $etiqueta.", 500);
        return 'uploads/' . $carpeta . '/' . $archivo;
    }

    // Borra una imagen anterior solo si está dentro de uploads/.
    public static function delete($ruta) {
        if (!$ruta || strpos($ruta, 'uploads/') !== 0) return;
        $real = realpath(__DIR__ . '/../../' . $ruta);
        $base = realpath(self::base());
        if ($real && $base && strpos($real, $base . DIRECTORY_SEPARATOR) === 0 && is_file($real)) @unlink($real);
    }
}
