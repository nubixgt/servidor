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
        public ?string $numero_cuenta = null,
        public ?string $banco = null,
        public ?string $tipo_cuenta = null,
        public ?string $foto_perfil = null,
        public ?string $dpi_anverso = null,
        public ?string $dpi_reverso = null,
        public ?float $historial_pagos_mensual = null,
        public ?float $historial_pagos_anual = null,
        public ?string $tipo_clientes_refiere = null,
        public ?int $cantidad_clientes = null
    ) {
    }
}
