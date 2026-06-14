<?php
namespace App\Entities;

class RegistroVacunacion
{
    public ?int $id;
    public string $fecha;
    public string $vacunador;
    public string $cliente;
    public string $direccion;
    public string $servicio;
    public int $cantidad;
    public float $costoPorAve;
    public float $total;
    public string $estado;

    public function __construct(
        string $fecha,
        string $vacunador,
        string $cliente,
        string $direccion,
        string $servicio,
        int $cantidad,
        float $costoPorAve,
        float $total,
        string $estado = 'Completado',
        ?int $id = null
    ) {
        $this->fecha = $fecha;
        $this->vacunador = $vacunador;
        $this->cliente = $cliente;
        $this->direccion = $direccion;
        $this->servicio = $servicio;
        $this->cantidad = $cantidad;
        $this->costoPorAve = $costoPorAve;
        $this->total = $total;
        $this->estado = $estado;
        $this->id = $id;
    }
}
