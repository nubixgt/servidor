<?php
namespace App\Entities;

class DigitalDocument
{
    public ?int $id = null;
    public string $tipo_documento;
    public ?int $project_id = null;
    public ?string $modulo_relacionado = null;
    public string $nombre_documento;
    public ?string $etiquetas = null;
    public string $archivo_path;
    public string $tipo_archivo;
    public int $peso_archivo = 0;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
