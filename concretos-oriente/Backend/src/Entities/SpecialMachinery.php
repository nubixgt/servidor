<?php
namespace App\Entities;

class SpecialMachinery
{
    public ?int $id = null;
    public string $nombre;
    public string $tipo_maquinaria;
    public string $subtipo;
    public ?string $marca = null;
    public ?string $modelo = null;
    public ?int $anio = null;
    public string $estado;
    public ?string $ubicacion = null;
    public ?int $responsable_id = null;
    public ?string $foto_1 = null;
    public ?string $foto_2 = null;
    public ?string $foto_3 = null;
    public ?string $foto_4 = null;
    public ?string $foto_5 = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
