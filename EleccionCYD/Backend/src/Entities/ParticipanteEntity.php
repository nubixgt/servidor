<?php
namespace App\Entities;

class ParticipanteEntity
{
    public function __construct(
        public ?int $id,
        public string $codigo,
        public string $nombre,
        public string $categoria,
        public bool $activo = true
    ) {
    }
}
