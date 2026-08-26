<?php
namespace App\DTOs\Productores;

class ProductorDTO
{
    public $dpi;
    public $nombre;
    public $apellido;
    public $finca;
    public $departamento;
    public $municipio;
    public $tipo;
    public $estado;
    public $telefono;
    public $email;
    public $direccion;
    public $fecha_registro;

    public function __construct($data)
    {
        $this->dpi = $data['dpi'] ?? null;
        $this->nombre = $data['nombre'] ?? null;
        $this->apellido = $data['apellido'] ?? null;
        $this->finca = $data['finca'] ?? null;
        $this->departamento = $data['departamento'] ?? null;
        $this->municipio = $data['municipio'] ?? null;
        $this->tipo = $data['tipo'] ?? 'AGRÍCOLA';
        $this->estado = $data['estado'] ?? 'ACTIVO';
        $this->telefono = $data['telefono'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->direccion = $data['direccion'] ?? null;
        $this->fecha_registro = $data['fecha_registro'] ?? date('Y-m-d');
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'dpi' => $this->dpi,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'finca' => $this->finca,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'fecha_registro' => $this->fecha_registro
        ];
    }

    public static function map($data)
    {
        return [
            'id' => $data['id'],
            'dpi' => $data['dpi'],
            'nombre' => trim(($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? '')),
            'finca' => $data['finca'] ?? 'Sin Finca',
            'ubicacion' => trim(($data['municipio'] ?? '') . ', ' . ($data['departamento'] ?? '')),
            'tipo' => $data['tipo'],
            'estado' => $data['estado']
        ];
    }

    public static function mapCollection($collection)
    {
        return array_map([self::class, 'map'], $collection);
    }
}
