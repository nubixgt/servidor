<?php
namespace App\Entities;

class Machinery
{
    public ?int $id = null;
    public string $categoria;
    public string $codigo_interno;
    public string $marca;
    public string $modelo;
    public ?string $numero_serie = null;
    public ?int $anio_fabricacion = null;
    public ?string $placa = null;
    public int $horometro_actual;
    public ?int $operador_id = null;
    public ?int $proyecto_id = null;
    public string $estado;
    public ?float $costo_adquisicion = null;
    public ?string $fecha_adquisicion = null;
    public ?string $foto_path = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
