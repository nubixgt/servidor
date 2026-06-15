<?php
namespace App\DTOs;

class InversionistaDTO
{
    public function __construct(
        public ?string $nombre,
        public ?float $capital,
        public ?string $banco,
        public ?string $numero_cuenta,
        public ?float $porcentaje
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['nombre'] ?? null,
            isset($data['capital']) && $data['capital'] !== '' ? (float)$data['capital'] : null,
            $data['banco'] ?? null,
            $data['numero_cuenta'] ?? null,
            isset($data['porcentaje']) && $data['porcentaje'] !== '' ? (float)$data['porcentaje'] : null
        );
    }
}
