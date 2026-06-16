<?php
namespace App\Entities;

class EgresoRegistroEntity
{
    public function __construct(
        public ?int $id = null,
        public ?int $egreso_id = null,
        public ?string $descripcion = null,
        public ?float $monto = null,
        public ?string $created_at = null
    ) {
    }
}
