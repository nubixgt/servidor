<?php
namespace App\DTOs\VISAN;

class EntregaDTO
{
    public $id;
    public $fecha;
    public $departamento;
    public $municipio;
    public $tipo_asistencia;
    public $raciones;
    public $familias;
    public $observaciones;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->fecha = $data['fecha'] ?? date('Y-m-d');
        $this->departamento = $data['departamento'] ?? null;
        $this->municipio = $data['municipio'] ?? null;
        $this->tipo_asistencia = $data['tipo_asistencia'] ?? null;
        $this->raciones = $data['raciones'] ?? 0;
        $this->familias = $data['familias'] ?? 0;
        $this->observaciones = $data['observaciones'] ?? null;
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'fecha' => $this->fecha,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'tipo_asistencia' => $this->tipo_asistencia,
            'raciones' => $this->raciones,
            'familias' => $this->familias,
            'observaciones' => $this->observaciones
        ];
    }
}
