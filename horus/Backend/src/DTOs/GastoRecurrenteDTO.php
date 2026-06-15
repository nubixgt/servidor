<?php

namespace App\DTOs;

class GastoRecurrenteDTO {
    public string $concepto;
    public string $descripcion;
    public float $monto;
    public int $dia_pago;
    public string $estado;

    public function __construct(array $data) {
        if (empty($data['concepto'])) throw new \InvalidArgumentException("El concepto es requerido.");
        if (!isset($data['monto']) || !is_numeric($data['monto'])) throw new \InvalidArgumentException("El monto es requerido y debe ser numérico.");
        if (empty($data['dia_pago']) || !is_numeric($data['dia_pago'])) throw new \InvalidArgumentException("El día de pago es requerido y debe ser numérico.");
        
        $this->concepto = $data['concepto'];
        $this->descripcion = $data['descripcion'] ?? '';
        $this->monto = (float)$data['monto'];
        $this->dia_pago = (int)$data['dia_pago'];
        $this->estado = $data['estado'] ?? 'ACTIVO';
    }
}
