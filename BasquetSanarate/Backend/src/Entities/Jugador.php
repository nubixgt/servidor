<?php
namespace App\Entities;

class Jugador
{
    public function __construct(
        public ?int $id = null,
        public ?int $equipo_id = null,
        public string $nombre_completo = '',
        public ?int $dorsal = null,
        public ?string $dpi = null,
        public ?string $fecha_nacimiento = null,
        public ?string $nacionalidad = 'Guatemalteca',
        public ?string $posicion = null,
        public ?int $estatura_cm = null,
        public ?int $peso_kg = null,
        public string $estado = 'Habilitado',
        public ?string $foto_ruta = null,
        public bool $activo = true
    ) {
    }
}
