<?php
namespace App\DTOs;

class NuevoUsuarioDTO
{
    public function __construct(
        public string $usuario,
        public string $nombre,
        public ?string $correo,
        public $rolId,
        public $departamentoId,
        public $municipioId
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim((string) ($data['usuario'] ?? '')), (string) ($data['nombre'] ?? ''), $data['correo'] ?? null,
            $data['rol_id'] ?? null, $data['departamento_id'] ?? null, $data['municipio_id'] ?? null
        );
    }
}
