<?php
namespace App\DTOs;

class NuevoRolDTO
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $descripcion
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self((string) ($data['codigo'] ?? ''), (string) ($data['nombre'] ?? ''), $data['descripcion'] ?? null);
    }
}
