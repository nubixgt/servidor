<?php
namespace App\Entities;

class PagoEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $fecha = null,
        public ?int $cliente_id = null,
        public ?string $referencia = null,
        public ?float $monto_pagado = null,
        public ?string $foto = null,
        public ?float $interes = null,
        public ?string $comprobante_interes = null,
        public ?string $fecha_interes = null,
        public ?float $capital = null,
        public ?string $comprobante_capital = null,
        public ?string $fecha_capital = null,
        public ?string $created_at = null
    ) {}
}
