<?php
namespace App\DTOs\VISAR;

class LicenciaDTO
{
    public $id;
    public $documento;
    public $tipo;
    public $subtipo;
    public $titular;
    public $identificacion;
    public $fecha_emision;
    public $fecha_vencimiento;
    public $estado;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->documento = $data['documento'] ?? null;
        $this->tipo = $data['tipo'] ?? null;
        $this->subtipo = $data['subtipo'] ?? null;
        $this->titular = $data['titular'] ?? null;
        $this->identificacion = $data['identificacion'] ?? null;
        $this->fecha_emision = $data['fecha_emision'] ?? null;
        $this->fecha_vencimiento = $data['fecha_vencimiento'] ?? null;
        $this->estado = $data['estado'] ?? 'EN TRAMITE';
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'documento' => $this->documento,
            'tipo' => $this->tipo,
            'subtipo' => $this->subtipo,
            'titular' => $this->titular,
            'identificacion' => $this->identificacion,
            'fecha_emision' => $this->fecha_emision,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'estado' => $this->estado
        ];
    }
}
