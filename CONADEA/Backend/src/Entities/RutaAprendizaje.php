<?php
namespace App\Entities;

class RutaAprendizaje
{
    /** @param int[] $cursoIds Ids de cursos asignados, en el orden en que se muestran */
    public function __construct(
        public ?int $id = null,
        public string $icono = '',
        public string $titulo = '',
        public string $descripcion = '',
        public string $color = 'esmeralda',
        public array $cursoIds = []
    ) {
    }
}
