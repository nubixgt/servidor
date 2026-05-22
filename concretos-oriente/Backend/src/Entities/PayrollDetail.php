<?php
namespace App\Entities;

class PayrollDetail
{
    public ?int $id = null;
    public int $payroll_id;
    public int $personnel_id;
    public float $salario_base_aplicado = 0.0;
    public int $horas_trabajadas = 160;
    public int $horas_extras = 0;
    public float $monto_horas_extras = 0.0;
    public float $bonificaciones = 0.0;
    public float $deducciones = 0.0;
    public float $total_neto = 0.0;
    public ?string $observaciones = null;
    public ?string $created_at = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
