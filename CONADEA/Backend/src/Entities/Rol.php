<?php
namespace App\Entities;

class Rol
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = ''
    ) {
    }
}
