<?php
namespace App\Entities;

class Leccion
{
    public function __construct(
        public ?int $id = null,
        public int $cursoId = 0,
        public int $orden = 0,
        public string $titulo = '',
        public string $contenido = '',
        public ?string $videoPath = null
    ) {
    }
}
