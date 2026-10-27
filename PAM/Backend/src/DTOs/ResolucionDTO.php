<?php
namespace App\DTOs;

class ResolucionDTO
{
    public function __construct(
        public ?string $fecha,
        public string $tipoCodigo,
        public ?string $numero,
        public ?string $descripcion
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self($data['fecha'] ?? null, (string) ($data['tipo_codigo'] ?? ''), $data['numero'] ?? null, $data['descripcion'] ?? null);
    }
}
