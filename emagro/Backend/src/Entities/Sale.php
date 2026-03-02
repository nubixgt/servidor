<?php
namespace App\Entities;

class Sale
{
    public $id;
    public $numero_nota;
    public $fecha;
    public $vendedor;
    public $cliente_id;
    public $cliente_nombre;
    public $nit;
    public $direccion;
    public $tipo_venta;
    public $dias_credito;
    public $subtotal;
    public $descuento_total;
    public $total;
    public $usuario_id;
}
