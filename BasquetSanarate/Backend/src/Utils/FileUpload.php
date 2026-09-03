<?php
namespace App\Utils;

use App\Core\HttpException;

class FileUpload
{
    /** Extensiones permitidas por tipo de MIME. */
    private const IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];
    private const PDF_MIME = [
        'application/pdf' => 'pdf',
    ];

    /**
     * Guarda un archivo subido y devuelve su ruta relativa (p. ej.
     * "uploads/equipos/3/logo.png") lista para almacenar en la columna *_ruta.
     *
     * @param array  $file    Elemento de $_FILES
     * @param string $tipo    Subcarpeta ("equipos", "jugadores", "novedades")
     * @param int    $id      Id de la entidad
     * @param string $nombre  Nombre base sin extensión ("logo", "foto", "portada", "doc")
     * @param string $kind    "image" o "pdf"
     * @param int    $maxBytes Tamaño máximo permitido
     */
    public static function save(
        array $file,
        string $tipo,
        int $id,
        string $nombre,
        string $kind = 'image',
        int $maxBytes = 2 * 1024 * 1024
    ): string {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw HttpException::validation('Archivo inválido');
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            throw HttpException::validation('No se recibió ningún archivo');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw HttpException::validation('Error al subir el archivo (código ' . $file['error'] . ')');
        }
        if ($file['size'] > $maxBytes) {
            throw HttpException::validation(
                'El archivo supera el tamaño máximo de ' . round($maxBytes / 1024 / 1024, 1) . ' MB'
            );
        }

        $allowed = $kind === 'pdf' ? self::PDF_MIME : self::IMAGE_MIME;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';

        if (!isset($allowed[$mime])) {
            throw HttpException::validation('Tipo de archivo no permitido (' . $mime . ')');
        }
        $ext = $allowed[$mime];

        $baseDir = dirname(__DIR__, 2) . '/uploads/' . $tipo . '/' . $id;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new HttpException('No se pudo crear el directorio de subida', 500);
        }

        // Borrar archivo previo con el mismo nombre y distinta extensión
        foreach (glob($baseDir . '/' . $nombre . '.*') ?: [] as $prev) {
            @unlink($prev);
        }

        $target = $baseDir . '/' . $nombre . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new HttpException('No se pudo guardar el archivo', 500);
        }
        @chmod($target, 0644);

        return 'uploads/' . $tipo . '/' . $id . '/' . $nombre . '.' . $ext;
    }
}
