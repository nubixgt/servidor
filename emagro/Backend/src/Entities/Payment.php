<?php
namespace App\Entities;

class Payment
{
    public $id;
    public $factura_id;
    public $fecha_pago;
    public $banco;
    public $monto_pago;
    public $referencia_transaccion;
    public $usuario_id;
}
