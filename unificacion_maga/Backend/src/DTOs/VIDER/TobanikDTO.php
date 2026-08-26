<?php
namespace App\DTOs\VIDER;

class TobanikDTO
{
    public $id;
    public $departamento;
    public $nombre_cooperativa;
    public $productores;
    public $monto_colocado;
    public $monto_otorgado;
    public $fecha_registro;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->departamento = $data['departamento'] ?? null;
        $this->nombre_cooperativa = $data['nombre_cooperativa'] ?? null;
        $this->productores = $data['productores'] ?? 0;
        $this->monto_colocado = $data['monto_colocado'] ?? 0;
        $this->monto_otorgado = $data['monto_otorgado'] ?? 0;
        $this->fecha_registro = $data['fecha_registro'] ?? date('Y-m-d');
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'departamento' => $this->departamento,
            'nombre_cooperativa' => $this->nombre_cooperativa,
            'productores' => $this->productores,
            'monto_colocado' => $this->monto_colocado,
            'monto_otorgado' => $this->monto_otorgado,
            'fecha_registro' => $this->fecha_registro
        ];
    }
}
