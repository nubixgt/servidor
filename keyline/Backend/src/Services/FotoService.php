<?php
namespace App\Services;

use App\Repositories\FotoRepository;
use App\Repositories\ParcelaRepository;
use App\Entities\Foto;

class FotoService
{
    private const MAX_SIZE = 12 * 1024 * 1024; // 12MB
    private const MAX_FILES = 8;
    private const ALLOWED_MIME = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];

    private FotoRepository $fotos;
    private ParcelaRepository $parcelas;
    private string $uploadsRoot;

    public function __construct()
    {
        $this->fotos = new FotoRepository();
        $this->parcelas = new ParcelaRepository();
        $this->uploadsRoot = dirname(__DIR__, 2) . '/uploads';
    }

    /**
     * Carpeta física de las fotos de una parcela: uploads/parcelas/{id}/fotos
     * (mismo patrón para cualquier otro apartado que en el futuro suba archivos:
     * uploads/{apartado}/{id}/...).
     */
    private function dirParaParcela(int $parcelaId): string
    {
        $dir = $this->uploadsRoot . '/parcelas/' . $parcelaId . '/fotos';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public function subir(int $parcelaId, array $files, string $subidoPor, string $caption, array $currentUser): array
    {
        $parcela = $this->parcelas->findById($parcelaId);
        if (!$parcela) {
            throw new \Exception('Parcela no encontrada.', 404);
        }
        if ($currentUser['role'] === 'tecnico' && $parcela->tecnicoId !== (int)$currentUser['id']) {
            throw new \Exception('No autorizado.', 403);
        }

        $normalized = $this->normalizeFilesArray($files);
        if (count($normalized) > self::MAX_FILES) {
            throw new \Exception('Máximo ' . self::MAX_FILES . ' fotos por carga.', 400);
        }

        $dir = $this->dirParaParcela($parcelaId);

        $creadas = [];
        foreach ($normalized as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }
            if ($file['size'] > self::MAX_SIZE) {
                throw new \Exception('Cada foto debe pesar máximo 12MB.', 400);
            }
            $mime = mime_content_type($file['tmp_name']);
            if (!isset(self::ALLOWED_MIME[$mime])) {
                throw new \Exception('Solo se permiten imágenes JPG, PNG o WEBP.', 400);
            }

            $ext = self::ALLOWED_MIME[$mime];
            $filename = sprintf('%d-%s%s', (int)(microtime(true) * 1000), bin2hex(random_bytes(6)), $ext);
            $destino = $dir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                continue;
            }

            $thumb = $this->generarMiniatura($dir, $destino, $filename, $mime);

            $foto = new Foto(
                parcelaId: $parcelaId,
                archivo: $filename,
                miniatura: $thumb,
                caption: $caption,
                subidoPor: $subidoPor,
            );
            $creadas[] = $this->fotos->create($foto);
        }

        return array_map(fn($f) => $f->toArray(), $creadas);
    }

    public function eliminar(int $parcelaId, int $fotoId, array $currentUser): void
    {
        $parcela = $this->parcelas->findById($parcelaId);
        if (!$parcela) {
            throw new \Exception('Parcela no encontrada.', 404);
        }
        if ($currentUser['role'] === 'tecnico' && $parcela->tecnicoId !== (int)$currentUser['id']) {
            throw new \Exception('No autorizado.', 403);
        }

        $foto = $this->fotos->findById($fotoId);
        if (!$foto || $foto->parcelaId !== $parcelaId) {
            throw new \Exception('Foto no encontrada.', 404);
        }

        $dir = $this->uploadsRoot . '/parcelas/' . $parcelaId . '/fotos';
        foreach ([$foto->archivo, $foto->miniatura] as $name) {
            if ($name) {
                $path = $dir . '/' . $name;
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
        $this->fotos->delete($fotoId);
    }

    /** Borra la carpeta completa uploads/parcelas/{id} (usado al eliminar una parcela). */
    public function eliminarCarpetaParcela(int $parcelaId): void
    {
        $dir = $this->uploadsRoot . '/parcelas/' . $parcelaId;
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    private function generarMiniatura(string $dir, string $origen, string $filename, string $mime): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($origen),
            'image/png' => @imagecreatefrompng($origen),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($origen) : false,
            default => false,
        };
        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $max = 480;
        $ratio = min($max / $width, $max / $height, 1);
        $newWidth = max(1, (int)round($width * $ratio));
        $newHeight = max(1, (int)round($height * $ratio));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        if ($mime === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $thumbName = 'thumb-' . $filename;
        $thumbPath = $dir . '/' . $thumbName;
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($thumb, $thumbPath, 82),
            'image/png' => imagepng($thumb, $thumbPath),
            'image/webp' => function_exists('imagewebp') ? imagewebp($thumb, $thumbPath, 82) : false,
            default => false,
        };

        imagedestroy($source);
        imagedestroy($thumb);

        return $ok ? $thumbName : null;
    }

    /** Normaliza $_FILES['fotos'] (array de campos paralelos) a una lista de archivos individuales. */
    private function normalizeFilesArray(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files];
        }
        $count = count($files['name']);
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $result[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }
        return $result;
    }
}
