<?php
namespace App\Entities;

class Vehicle
{
    public ?int $id = null;
    public string $placa;
    public float $kilometraje;
    public string $marca;
    public string $modelo;
    public ?int $piloto_id = null;
    public ?string $observaciones = null;
    public ?string $foto_frontal = null;
    public ?string $foto_trasera = null;
    public string $frecuencia_prioridad;
    public string $estatus;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
