<?php
namespace App\Entities;

class Supplier
{
    public ?int $id = null;
    public string $razon_social;
    public string $nit;
    public string $direccion;
    public string $telefono;
    public ?string $correo_electronico = null;
    public ?string $contacto_principal = null;
    public string $condicion_pago = 'Contado';
    public ?int $dias_credito = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
