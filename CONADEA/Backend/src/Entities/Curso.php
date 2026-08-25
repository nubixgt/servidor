<?php
namespace App\Entities;

class Curso
{
    /**
     * @param Leccion[] $lecciones
     */
    public function __construct(
        public ?int $id = null,
        public string $icono = '',
        public string $titulo = '',
        public string $descripcion = '',
        public string $imagenPath = '',
        public array $lecciones = [],
        public int $totalLecciones = 0
    ) {
    }
}
