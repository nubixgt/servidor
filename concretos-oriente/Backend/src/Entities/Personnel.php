<?php
namespace App\Entities;

class Personnel
{
    public ?int $id = null;
    public string $tipo_empleado;
    public string $nombres;
    public string $apellidos;
    public string $dpi;
    public ?string $nit = null;
    public ?string $telefono = null;
    public ?string $direccion = null;
    public string $puesto;
    public string $tipo_planilla;
    public float $salario_base;
    public ?float $tarifa_hora_extra = null;
    public string $fecha_contratacion;
    public ?string $fecha_baja = null;
    public ?string $numero_cuenta = null;
    public ?string $nombre_banco = null;
    public ?int $proyecto_id = null;
    public ?string $foto_path = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
