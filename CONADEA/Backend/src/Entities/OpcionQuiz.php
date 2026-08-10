<?php
namespace App\Entities;

class OpcionQuiz
{
    public function __construct(
        public ?int $id = null,
        public int $preguntaId = 0,
        public int $orden = 0,
        public string $texto = '',
        public bool $esCorrecta = false
    ) {
    }
}
