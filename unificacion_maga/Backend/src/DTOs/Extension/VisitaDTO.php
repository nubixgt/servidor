<?php
namespace App\DTOs\Extension;

class VisitaDTO
{
    public $codigo;
    public $fecha;
    public $departamento;
    public $municipio;
    public $comunidad;
    public $tipo;
    public $extensionista_id;
    public $estado;
    public $beneficiarios;
    public $observaciones;
    public $latitud;
    public $longitud;

    public function __construct($data)
    {
        $this->codigo = $data['codigo'] ?? null;
        $this->fecha = $data['fecha'] ?? date('Y-m-d');
        $this->departamento = $data['departamento'] ?? null;
        $this->municipio = $data['municipio'] ?? null;
        $this->comunidad = $data['comunidad'] ?? null;
        $this->tipo = $data['tipo'] ?? 'ASISTENCIA';
        $this->extensionista_id = $data['extensionista_id'] ?? null;
        $this->estado = $data['estado'] ?? 'PROGRAMADA';
        $this->beneficiarios = $data['beneficiarios'] ?? 0;
        $this->observaciones = $data['observaciones'] ?? null;
        $this->latitud = $data['latitud'] ?? null;
        $this->longitud = $data['longitud'] ?? null;
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'codigo' => $this->codigo,
            'fecha' => $this->fecha,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'comunidad' => $this->comunidad,
            'tipo' => $this->tipo,
            'extensionista_id' => $this->extensionista_id,
            'estado' => $this->estado,
            'beneficiarios' => $this->beneficiarios,
            'observaciones' => $this->observaciones,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud
        ];
    }

    public static function map($data)
    {
        return [
            'id' => $data['id'],
            'codigo' => $data['codigo'],
            'fecha' => date('d M Y', strtotime($data['fecha'])),
            'departamento' => $data['departamento'],
            'municipio' => $data['municipio'],
            'comunidad' => $data['comunidad'],
            'tipo' => $data['tipo'],
            'encargado' => $data['extensionista'] ?? 'No asignado',
            'estado' => $data['estado'],
            'beneficiarios' => (int)$data['beneficiarios'],
            'latitud' => $data['latitud'],
            'longitud' => $data['longitud']
        ];
    }

    public static function mapCollection($collection)
    {
        return array_map([self::class, 'map'], $collection);
    }
}
