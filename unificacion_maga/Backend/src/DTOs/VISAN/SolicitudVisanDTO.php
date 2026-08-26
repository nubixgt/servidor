<?php
namespace App\DTOs\VISAN;

class SolicitudVisanDTO
{
    public $id;
    public $id_solicitud;
    public $fecha;
    public $productor_id;
    public $comunidad;
    public $programa;
    public $estado;
    public $observaciones;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->id_solicitud = $data['id_solicitud'] ?? null;
        $this->fecha = $data['fecha'] ?? date('Y-m-d');
        $this->productor_id = $data['productor_id'] ?? null;
        $this->comunidad = $data['comunidad'] ?? null;
        $this->programa = $data['programa'] ?? 'ASISTENCIA_ALIMENTARIA';
        $this->estado = $data['estado'] ?? 'PROGRAMADO';
        $this->observaciones = $data['observaciones'] ?? null;
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'id_solicitud' => $this->id_solicitud,
            'fecha' => $this->fecha,
            'productor_id' => $this->productor_id,
            'comunidad' => $this->comunidad,
            'programa' => $this->programa,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones
        ];
    }
}
