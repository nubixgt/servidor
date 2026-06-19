<?php
namespace App\Entities;

class Vehicle
{
    public ?int $id = null;
    public string $placa;
    public string $tipo_vehiculo;
    public ?string $tipo_seguro = null;
    public ?string $ubicacion = null;
    public ?float $precio = null;
    public float $kilometraje;
    public string $marca;
    public string $modelo;
    public ?int $piloto_id = null;
    public ?string $foto_delantera = null;
    public ?string $foto_trasera = null;
    public ?string $foto_lateral1 = null;
    public ?string $foto_lateral2 = null;
    public string $estatus;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
