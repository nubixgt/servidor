<?php
namespace App\Entities;

class Partido
{
    public function __construct(
        public ?int $id = null,
        public ?int $jornada = null,
        public string $fase = 'Regular',
        public int $equipo_local_id = 0,
        public int $equipo_visitante_id = 0,
        public ?string $fecha = null,
        public ?string $hora = null,
        public ?string $sede = null,
        public string $estado = 'Programado',
        public int $marcador_local = 0,
        public int $marcador_visitante = 0,
        public ?string $arbitro_principal = null,
        public ?string $juez_mesa = null,
        public bool $acta_cerrada = false
    ) {
    }
}
