<?php
namespace App\DTOs;

class CambioEstadoDTO
{
    public function __construct(
        public string $estadoCodigo,
        public ?string $fecha,
        public string $motivo
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self((string) ($data['estado_codigo'] ?? ''), $data['fecha'] ?? null, (string) ($data['motivo'] ?? ''));
    }
}
