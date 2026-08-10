<?php
namespace App\Entities;

class Usuario
{
    public function __construct(
        public ?int $id = null,
        public string $nombreCompleto = '',
        public string $usuario = '',
        public string $passwordHash = '',
        public string $telefono = '',
        public int $departamentoId = 0,
        public int $municipioId = 0,
        public int $rolId = 0,
        public string $rolNombre = '',
        public bool $activo = true
    ) {
    }
}
