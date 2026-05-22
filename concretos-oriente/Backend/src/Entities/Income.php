<?php
namespace App\Entities;

class Income
{
    public ?int $id = null;
    public ?int $proyecto_id = null;
    public string $tipo_ingreso;
    public float $monto;
    public string $fecha_ingreso;
    public string $cuenta_bancaria;
    public ?string $numero_cheque = null;
    public ?string $pagador = null;
    public ?string $descripcion = null;
    public ?string $comprobante_path = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
