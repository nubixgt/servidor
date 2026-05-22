<?php
namespace App\Entities;

class User
{
    public ?int $id = null;
    public string $nombre;
    public string $usuario;
    public string $password;
    public string $rol = 'admin';
    public string $estado = 'Activo';
    public ?string $foto = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
