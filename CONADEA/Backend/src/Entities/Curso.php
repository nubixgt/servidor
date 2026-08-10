<?php
namespace App\Entities;

class Curso
{
    /**
     * @param Leccion[] $lecciones
     * @param PreguntaQuiz[] $quiz
     */
    public function __construct(
        public ?int $id = null,
        public string $icono = '',
        public string $titulo = '',
        public string $descripcion = '',
        public string $imagenUrl = '',
        public array $lecciones = [],
        public array $quiz = []
    ) {
    }
}
