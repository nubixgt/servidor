<?php
namespace App\Entities;

class Equipo
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = '',
        public string $sede = '',
        public string $conferencia = 'Norte',
        public string $rama = 'Masculina Mayor',
        public ?string $logo_ruta = null,
        public string $director_tecnico = '',
        public string $telefono_delegado = '',
        public ?string $color_hex = null,
        public bool $activo = true
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['nombre'] ?? '',
            $row['sede'] ?? '',
            $row['conferencia'] ?? 'Norte',
            $row['rama'] ?? 'Masculina Mayor',
            $row['logo_ruta'] ?? null,
            $row['director_tecnico'] ?? '',
            $row['telefono_delegado'] ?? '',
            $row['color_hex'] ?? null,
            (bool) ($row['activo'] ?? 1)
        );
    }
}
