<?php
namespace App\DTOs;

class MovimientoNominaDTO
{
    public function __construct(
        public string $tipo,
        public ?string $fecha,
        public ?string $motivo,
        public ?string $estadoCodigo
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self((string) ($data['tipo'] ?? ''), $data['fecha'] ?? null, $data['motivo'] ?? null, $data['estado_codigo'] ?? null);
    }
}
