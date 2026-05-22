<?php
namespace App\Entities;

class AlertConfig
{
    public ?int $id = null;
    public string $nombre;
    public string $tipo_evento;
    public string $canales = '[]';
    public string $destinatarios = '[]';
    public float $umbral = 0.0;
    public int $activa = 1;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
