<?php
namespace App\Entities;

class Departamento
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = ''
    ) {
    }
}
