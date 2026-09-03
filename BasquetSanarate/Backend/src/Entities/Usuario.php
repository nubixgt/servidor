<?php
namespace App\Entities;

class Usuario
{
    public function __construct(
        public ?int $id = null,
        public string $usuario = '',
        public string $nombre = '',
        public string $rol = 'admin',
        public bool $activo = true
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['usuario'],
            $row['nombre'],
            $row['rol'] ?? 'admin',
            (bool) ($row['activo'] ?? 1)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario' => $this->usuario,
            'nombre' => $this->nombre,
            'rol' => $this->rol,
            'activo' => $this->activo,
        ];
    }
}
