<?php
namespace App\Entities;

class PrestamoEntity
{
    public function __construct(
        public ?int $id = null,
        public ?int $cliente_id = null,
        public ?float $monto_principal = null,
        public ?float $tasa = null,
        public ?string $tipo_interes = null,
        public ?int $plazo = null,
        public ?string $fecha_desembolso = null,
        public ?float $total_intereses = null,
        public ?float $total_seguro = null,
        public ?float $costo_total = null,
        public ?string $estado = 'ACTIVO',
        public ?string $created_at = null
    ) {
    }
}
