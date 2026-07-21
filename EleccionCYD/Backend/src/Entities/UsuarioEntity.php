<?php
namespace App\Entities;

class UsuarioEntity
{
    public function __construct(
        public ?int $id,
        public string $usuario,
        public string $password,
        public string $nombre,
        public string $rol = 'admin',
        public bool $activo = true
    ) {
    }
}
