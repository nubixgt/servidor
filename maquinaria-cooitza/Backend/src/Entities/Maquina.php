<?php
namespace App\Entities;

class Maquina
{
    public $id;
    public $marca;
    public $tipo;
    public $identificador;
    public $foto_path;
    public $estado;
    public $horas_acumuladas;
    public $proximo_servicio;
    public $piloto_id;
    public $created_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->marca = $data['marca'] ?? '';
        $this->tipo = $data['tipo'] ?? 'Excavadora';
        $this->identificador = $data['identificador'] ?? '';
        $this->foto_path = $data['foto_path'] ?? null;
        $this->estado = $data['estado'] ?? 'Operativo';
        $this->horas_acumuladas = (float)($data['horas_acumuladas'] ?? 0);
        $this->proximo_servicio = $data['proximo_servicio'] ?? 'Sin Programar';
        $this->piloto_id = $data['piloto_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
}
