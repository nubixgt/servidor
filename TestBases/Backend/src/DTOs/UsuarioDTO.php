<?php
namespace App\DTOs;

class UsuarioDTO
{
    public const ROLES = ['admin', 'tecnico'];

    public function __construct(
        public string $usuario,
        public string $nombre,
        public string $rol,
        public ?string $clave,
        public bool $activo
    ) {
    }

    public static function fromRequest(array $d): self
    {
        $clave = isset($d['clave']) && $d['clave'] !== '' ? (string) $d['clave'] : null;
        return new self(
            strtolower(trim((string) ($d['usuario'] ?? ''))),
            preg_replace('/\s+/', ' ', trim((string) ($d['nombre'] ?? ''))),
            (string) ($d['rol'] ?? ''),
            $clave,
            filter_var($d['activo'] ?? true, FILTER_VALIDATE_BOOLEAN)
        );
    }
}
