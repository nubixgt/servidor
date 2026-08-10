<?php
namespace App\Entities;

class PreguntaQuiz
{
    /** @param OpcionQuiz[] $opciones */
    public function __construct(
        public ?int $id = null,
        public int $cursoId = 0,
        public int $orden = 0,
        public string $pregunta = '',
        public array $opciones = []
    ) {
    }
}
