<?php
namespace App\DTOs;

class TipoResolucionDTO
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public $familiaId,
        public bool $activo
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self((string) ($data['codigo'] ?? ''), (string) ($data['nombre'] ?? ''), $data['familia_id'] ?? null, (bool) ($data['activo'] ?? true));
    }
}
