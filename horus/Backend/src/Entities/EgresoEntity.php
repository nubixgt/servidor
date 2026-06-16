<?php
namespace App\Entities;

class EgresoEntity
{
    public function __construct(
        public ?int $id = null,
        public ?int $recurrente_id = null,
        public ?string $tipo_egreso = null,
        public ?string $fecha = null,
        public ?string $referencia = null,
        public ?string $pagador = null,
        public ?string $comprobante = null,
        public ?string $descripcion_concepto = null,
        public ?string $created_at = null,
        public array $registros = []
    ) {
    }
}
