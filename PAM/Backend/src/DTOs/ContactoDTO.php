<?php
namespace App\DTOs;

class ContactoDTO
{
    public function __construct(
        public ?string $telefono,
        public ?string $direccion
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self($data['telefono'] ?? null, $data['direccion'] ?? null);
    }
}
