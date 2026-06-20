<?php
namespace App\Entities;

class MechanicRecord
{
    public ?int    $id           = null;
    public string  $fecha;
    public string  $placa;
    public string  $tipo_unidad;
    public ?int    $proveedor_id = null;
    public ?string $foto_1       = null;
    public ?string $foto_2       = null;
    public ?string $foto_3       = null;
    public ?string $foto_4       = null;
    public ?string $foto_5       = null;
}
