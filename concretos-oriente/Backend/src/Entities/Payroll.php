<?php
namespace App\Entities;

class Payroll
{
    public ?int $id = null;
    public string $periodo;
    public string $fecha_corte;
    public string $estado = 'Borrador';
    public float $total_pagado = 0.0;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
