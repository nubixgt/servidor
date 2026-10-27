<?php
namespace App\DTOs;

class VisitaDTO
{
    public function __construct(
        public ?string $fecha,
        public string $resultado,
        public ?string $observacion,
        public ?string $tecnico
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self($data['fecha'] ?? null, (string) ($data['resultado'] ?? ''), $data['observacion'] ?? null, $data['tecnico'] ?? null);
    }
}
