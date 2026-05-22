<?php
namespace App\Entities;

class AlertHistory
{
    public ?int $id = null;
    public int $alert_config_id;
    public string $mensaje;
    public string $estado = 'Pendiente';
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
