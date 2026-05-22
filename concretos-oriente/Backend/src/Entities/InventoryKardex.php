<?php
namespace App\Entities;

class InventoryKardex
{
    public ?int $id = null;
    public string $tipo_movimiento;
    public int $item_id;
    public ?int $proyecto_origen_id = null;
    public ?int $proyecto_destino_id = null;
    public float $cantidad;
    public float $costo_unitario = 0.00;
    public ?string $referencia_documento = null;
    public ?string $notas = null;
    public string $fecha_movimiento;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
