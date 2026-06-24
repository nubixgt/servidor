<?php
namespace App\Entities;

class PadreEntity
{
    public function __construct(
        public ?int $id,
        public string $nombreCompleto,
        public string $telefono,
        public string $correo,
        public string $direccion,
        public string $departamento,
        public ?string $fechaRegistro = null
    ) {
    }
}
