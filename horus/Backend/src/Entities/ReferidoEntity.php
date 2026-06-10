<?php
namespace App\Entities;

class ReferidoEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $nombre = null,
        public ?string $dpi = null,
        public ?string $telefono = null,
        public ?string $direccion = null,
        public ?string $numeroCuenta = null,
        public ?string $banco = null,
        public ?string $tipoCuenta = null,
        public ?string $fotoPerfil = null,
        public ?string $dpiAnverso = null,
        public ?string $dpiReverso = null
    ) {
    }
}
