<?php
namespace App\Entities;

class FuelRecord
{
    public ?int $id = null;
    public string $fecha;
    public ?int $piloto_id = null;
    public string $placa;
    public string $tipo_unidad;
    public ?int $proyecto_id = null;
    public float $cantidad_galones;
    public float $monto;
    public ?float $kilometraje = null;
    public ?float $horometro = null;
    public ?string $foto_1 = null;
    public ?string $foto_2 = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
