<?php
namespace App\Entities;

class Municipio
{
    public function __construct(
        public ?int $id = null,
        public int $departamentoId = 0,
        public string $nombre = ''
    ) {
    }
}
