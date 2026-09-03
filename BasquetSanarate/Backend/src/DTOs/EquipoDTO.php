<?php
namespace App\DTOs;

class EquipoDTO
{
    public function __construct(
        public string $nombre,
        public string $sede,
        public string $conferencia,
        public string $rama,
        public string $director_tecnico,
        public string $telefono_delegado,
        public ?string $color_hex = null
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            trim($data['nombre'] ?? ''),
            trim($data['sede'] ?? ''),
            trim($data['conferencia'] ?? ''),
            trim($data['rama'] ?? ''),
            trim($data['director_tecnico'] ?? ''),
            trim($data['telefono_delegado'] ?? ''),
            isset($data['color_hex']) && $data['color_hex'] !== '' ? trim($data['color_hex']) : null
        );
    }
}
