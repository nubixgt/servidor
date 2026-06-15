<?php
namespace App\Entities;

class InversionistaEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $nombre = null,
        public ?float $capital = null,
        public ?string $banco = null,
        public ?string $numero_cuenta = null,
        public ?float $porcentaje = null,
        public ?string $documentos = null,
        public ?string $created_at = null
    ) {
    }
}
