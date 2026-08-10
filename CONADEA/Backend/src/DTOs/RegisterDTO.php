<?php
namespace App\DTOs;

class RegisterDTO
{
    public function __construct(
        public string $nombreCompleto,
        public string $usuario,
        public string $password,
        public string $telefono,
        public int $departamentoId,
        public int $municipioId
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim($data['nombre_completo'] ?? ''),
            trim($data['usuario'] ?? ''),
            (string) ($data['password'] ?? ''),
            trim($data['telefono'] ?? ''),
            (int) ($data['departamento_id'] ?? 0),
            (int) ($data['municipio_id'] ?? 0)
        );
    }
}
