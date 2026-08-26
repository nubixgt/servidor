<?php
namespace App\DTOs\VIDER;

class EjecucionDTO
{
    public $id;
    public $fecha;
    public $departamento;
    public $municipio;
    public $dependencia_id;
    public $actividad_id;
    public $producto_id;
    public $intervencion_id;
    public $genero;
    public $fisico_tipo;
    public $fisico_planificado;
    public $fisico_ejecutado;
    public $financiero_vigente;
    public $financiero_ejecutado;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->fecha = $data['fecha'] ?? date('Y-m-d');
        $this->departamento = $data['departamento'] ?? null;
        $this->municipio = $data['municipio'] ?? null;
        $this->dependencia_id = $data['dependencia_id'] ?? null;
        $this->actividad_id = $data['actividad_id'] ?? null;
        $this->producto_id = $data['producto_id'] ?? null;
        $this->intervencion_id = $data['intervencion_id'] ?? null;
        $this->genero = $data['genero'] ?? 'N/D';
        $this->fisico_tipo = $data['fisico_tipo'] ?? 'PERSONAS';
        $this->fisico_planificado = $data['fisico_planificado'] ?? 0;
        $this->fisico_ejecutado = $data['fisico_ejecutado'] ?? 0;
        $this->financiero_vigente = $data['financiero_vigente'] ?? 0;
        $this->financiero_ejecutado = $data['financiero_ejecutado'] ?? 0;
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
            'dependencia_id' => $this->dependencia_id,
            'actividad_id' => $this->actividad_id,
            'producto_id' => $this->producto_id,
            'intervencion_id' => $this->intervencion_id,
            'genero' => $this->genero,
            'fisico_tipo' => $this->fisico_tipo,
            'fisico_planificado' => $this->fisico_planificado,
            'fisico_ejecutado' => $this->fisico_ejecutado,
            'financiero_vigente' => $this->financiero_vigente,
            'financiero_ejecutado' => $this->financiero_ejecutado
        ];
    }
}
