<?php
namespace App\Entities;

class Expense
{
    public ?int $id = null;
    public ?int $proyecto_id = null;
    public string $tipo_egreso;
    public float $monto;
    public string $fecha_egreso;
    public ?string $cuenta_origen = null;
    public ?string $numero_cheque = null;
    public string $beneficiario;
    public ?string $descripcion = null;
    public ?string $comprobante_path = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
