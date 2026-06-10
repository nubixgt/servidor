<?php
namespace App\Entities;

class Vehiculo
{
    public $id;
    public $marca;
    public $placa;
    public $tipo;
    public $modelo;
    public $kilometraje_registro;
    public $piloto_id;
    public $foto;
    public $status;
    public $created_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->marca = $data['marca'] ?? null;
        $this->placa = $data['placa'] ?? null;
        $this->tipo = $data['tipo'] ?? null;
        $this->modelo = $data['modelo'] ?? null;
        $this->kilometraje_registro = $data['kilometraje_registro'] ?? 0;
        $this->piloto_id = $data['piloto_id'] ?? null;
        $this->foto = $data['foto'] ?? null;
        $this->status = $data['status'] ?? 'activo';
        $this->created_at = $data['created_at'] ?? null;
    }
}
