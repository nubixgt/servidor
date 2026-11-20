<?php
namespace App\Entities;

class Usuario
{
    public function __construct(
        public int $id,
        public string $usuario,
        public string $nombreCompleto,
        public string $rol,
        public string $claveHash,
        public bool $activo,
        public int $intentosFallidos,
        public ?string $bloqueadoHasta
    ) {
    }

    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'],
            $r['usuario'],
            $r['nombre_completo'],
            $r['rol'],
            $r['clave_hash'],
            (bool) $r['activo'],
            (int) $r['intentos_fallidos'],
            $r['bloqueado_hasta']
        );
    }

    /** Datos que se pueden mostrar al cliente (nunca la clave). */
    public function publico(): array
    {
        return [
            'id' => $this->id,
            'usuario' => $this->usuario,
            'nombre' => $this->nombreCompleto,
            'rol' => $this->rol,
        ];
    }
}
