<?php
namespace App\Entities;

class MaintenanceLog
{
    public ?int $id = null;
    public int $machinery_id;
    public string $tipo_mantenimiento = 'Preventivo';
    public string $fecha_mantenimiento;
    public string $descripcion;
    public float $costo_total = 0.0;
    public ?int $responsable_id = null;
    public ?string $proximo_mantenimiento = null;
    public float $horometro_servicio = 0.0;
    public ?string $observaciones = null;
    public ?string $latitud = null;
    public ?string $longitud = null;
    public ?string $path_fotos = null;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
