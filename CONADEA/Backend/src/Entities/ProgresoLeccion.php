<?php
namespace App\Entities;

class ProgresoLeccion
{
    public function __construct(
        public int $usuarioId,
        public int $leccionId,
        public int $cursoId,
        public bool $completada = false,
        public int $segundosVideo = 0
    ) {
    }
}
