<?php
namespace App\Entities;

class PurchaseOrder
{
    public ?int $id = null;
    public int $proveedor_id;
    public int $proyecto_id;
    public string $fecha_orden;
    public string $condicion_pago = 'Contado';
    public ?string $observaciones = null;
    public ?string $archivo_adjunto = null;
    public float $total;
    public string $estado = 'Pendiente';

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
