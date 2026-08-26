<?php
namespace App\DTOs\VISAR;

class InspeccionDTO
{
    public $id;
    public $codigo;
    public $area;
    public $productor;
    public $ubicacion;
    public $fecha;
    public $motivo;
    public $estado;
    public $riesgo;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->codigo = $data['codigo'] ?? null;
        $this->area = $data['area'] ?? null;
        $this->productor = $data['productor'] ?? null;
        $this->ubicacion = $data['ubicacion'] ?? null;
        $this->fecha = $data['fecha'] ?? null;
        $this->motivo = $data['motivo'] ?? null;
        $this->estado = $data['estado'] ?? 'AGENDA';
        $this->riesgo = $data['riesgo'] ?? 'BAJO';
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'codigo' => $this->codigo,
            'area' => $this->area,
            'productor' => $this->productor,
            'ubicacion' => $this->ubicacion,
            'fecha' => $this->fecha,
            'motivo' => $this->motivo,
            'estado' => $this->estado,
            'riesgo' => $this->riesgo
        ];
    }

    public static function map($data)
    {
        return [
            'id' => $data['id'],
            'codigo' => $data['codigo'],
            'area' => $data['area'],
            'productor' => $data['productor'],
            'ubicacion' => $data['ubicacion'],
            'fecha' => date('d M Y', strtotime($data['fecha'])),
            'motivo' => $data['motivo'],
            'estado' => $data['estado'],
            'riesgo' => $data['riesgo']
        ];
    }

    public static function mapCollection($collection)
    {
        return array_map([self::class, 'map'], $collection);
    }
}
