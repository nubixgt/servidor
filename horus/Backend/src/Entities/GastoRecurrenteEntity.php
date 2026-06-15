<?php

namespace App\Entities;

class GastoRecurrenteEntity {
    private ?int $id;
    private string $concepto;
    private string $descripcion;
    private float $monto;
    private int $diaPago;
    private string $estado;
    private ?string $createdAt;

    public function __construct(?int $id, string $concepto, string $descripcion, float $monto, int $diaPago, string $estado, ?string $createdAt = null) {
        $this->id = $id;
        $this->concepto = $concepto;
        $this->descripcion = $descripcion;
        $this->monto = $monto;
        $this->diaPago = $diaPago;
        $this->estado = $estado;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getConcepto(): string { return $this->concepto; }
    public function getDescripcion(): string { return $this->descripcion; }
    public function getMonto(): float { return $this->monto; }
    public function getDiaPago(): int { return $this->diaPago; }
    public function getEstado(): string { return $this->estado; }
    public function getCreatedAt(): ?string { return $this->createdAt; }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'concepto' => $this->concepto,
            'descripcion' => $this->descripcion,
            'monto' => $this->monto,
            'dia_pago' => $this->diaPago,
            'estado' => $this->estado,
            'created_at' => $this->createdAt
        ];
    }
}
