<?php
namespace App\Entities;

class ProgresoCurso
{
    public function __construct(
        public int $usuarioId,
        public int $cursoId,
        public ?int $nota = null,
        public bool $aprobado = false,
        public ?string $fechaAprobado = null
    ) {
    }
}
