<?php
namespace App\Entities;

class Contractor
{
    public ?int $id = null;
    public string $nombre;
    public ?string $telefono = null;
    public ?string $correo_electronico = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
