<?php
namespace App\DTOs;

class SolicitudDTO
{
    public function __construct(
        public string $nombre,
        public ?string $dpi,
        public ?string $fechaNacimiento,
        public ?string $telefono,
        public ?string $direccion,
        public $municipioId,
        public ?string $fechaSolicitud,
        public ?string $numero
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            (string) ($data['nombre'] ?? ''), $data['dpi'] ?? null, $data['fecha_nacimiento'] ?? null,
            $data['telefono'] ?? null, $data['direccion'] ?? null, $data['municipio_id'] ?? null,
            $data['fecha_solicitud'] ?? null, $data['numero'] ?? null
        );
    }
}
