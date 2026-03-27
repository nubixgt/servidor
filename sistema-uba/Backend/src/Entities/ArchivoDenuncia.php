<?php
namespace App\Entities;

class ArchivoDenuncia
{
    public ?int $id = null;
    public int $denunciaId;
    public string $tipoArchivo;
    public string $rutaArchivo;
    public ?string $nombreOriginal;
    public ?string $mimeType;
    public ?string $fechaSubida = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'denuncia_id' => $this->denunciaId,
            'tipo_archivo' => $this->tipoArchivo,
            'ruta_archivo' => $this->rutaArchivo,
            'nombre_original' => $this->nombreOriginal,
            'mime_type' => $this->mimeType
        ];
    }
}
