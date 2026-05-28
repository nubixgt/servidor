<?php
namespace App\Utils;

use Exception;

class Uploader
{
    private string $targetDir;
    private string $relativePath;

    public function __construct(string $directory)
    {
        // El directorio base es la raíz de Backend
        $baseDir = __DIR__ . '/../../';
        $this->relativePath = ltrim($directory, '/');
        $this->targetDir = $baseDir . $this->relativePath;
        
        if (!is_dir($this->targetDir)) {
            if (!@mkdir($this->targetDir, 0777, true)) {
                throw new Exception("No se pudo crear el directorio de subida: {$this->relativePath}");
            }
        }
    }

    public function upload(array $file, string $filenamePrefix): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo (Código: " . $file['error'] . ").");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $filenamePrefix . '.' . $extension;
        $targetPath = rtrim($this->targetDir, '/') . '/' . $filename;

        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Error al guardar el archivo en el servidor.");
        }

        // Devolver la ruta relativa a la carpeta Backend para que el frontend pueda armar la URL
        return rtrim($this->relativePath, '/') . '/' . $filename;
    }
}
