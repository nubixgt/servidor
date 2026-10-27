<?php
namespace App\DTOs;

class CasoDTO
{
    public function __construct(
        public string $regla,
        public string $estado,
        public ?string $comentario
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self((string) ($data['regla'] ?? ''), (string) ($data['estado'] ?? ''), $data['comentario'] ?? null);
    }
}
