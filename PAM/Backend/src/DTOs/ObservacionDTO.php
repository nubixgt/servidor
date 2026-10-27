<?php
namespace App\DTOs;

class ObservacionDTO
{
    public function __construct(
        public string $texto,
        public ?string $fecha
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self((string) ($data['texto'] ?? ''), $data['fecha'] ?? null);
    }
}
