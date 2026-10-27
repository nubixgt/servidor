<?php
namespace App\DTOs;

class ExportacionDTO
{
    public function __construct(
        public string $tipo,
        public string $formato,
        public string $motivo,
        public FiltrosDTO $filtros
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            (string) ($data['tipo'] ?? ''),
            strtolower((string) ($data['formato'] ?? 'csv')),
            (string) ($data['motivo'] ?? ''),
            FiltrosDTO::fromRequest(is_array($data['filtros'] ?? null) ? $data['filtros'] : [])
        );
    }
}
