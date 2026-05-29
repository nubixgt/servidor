<?php
namespace App\Entities;

class VehicleLog
{
    public ?int $id = null;
    public int $vehiculo_id;
    public ?int $piloto_id = null;
    public string $estatus_vehiculo;
    public ?string $envio_servicio = null;
    public ?string $reportar_averia = null;
    public ?string $observaciones = null;
    public ?string $fecha_registro = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vehiculo_id' => $this->vehiculo_id,
            'piloto_id' => $this->piloto_id,
            'estatus_vehiculo' => $this->estatus_vehiculo,
            'envio_servicio' => $this->envio_servicio,
            'reportar_averia' => $this->reportar_averia,
            'observaciones' => $this->observaciones,
            'fecha_registro' => $this->fecha_registro,
        ];
    }
}
