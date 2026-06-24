<?php
namespace App\DTOs;

class PadreDTO
{
    public function __construct(
        public string $nombreCompleto,
        public string $telefono,
        public string $correo,
        public string $direccion,
        public string $departamento
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim($data['nombre_completo'] ?? ''),
            trim($data['telefono'] ?? ''),
            strtolower(trim($data['correo'] ?? '')),
            trim($data['direccion'] ?? ''),
            trim($data['departamento'] ?? '')
        );
    }
}
