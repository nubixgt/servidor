<?php
namespace App\Entities;

class DanoInfraestructura
{
    public function __construct(
        public ?int $id = null,
        public ?int $usuarioId = null,
        public ?string $registradoPor = null,
        public string $fechaInforme = '',
        public ?string $monitoreoInicio = null,
        public ?string $monitoreoFin = null,
        public ?int $semana = null,
        public ?int $anioSemana = null,
        public string $tipoInfraestructura = '',
        public ?string $tipoOtra = null,
        public string $departamento = '',
        public string $municipio = '',
        public string $centroPoblado = '',
        public $gpsLat = null,
        public $gpsLon = null,
        public $gpsPrecision = null,
        public ?string $eventoReportado = null,
        public string $causa = '',
        public ?string $causaOtra = null,
        public ?int $famAfectadas = null,
        public $perdidaEconomica = null,
        public ?string $notas = null,
        public ?string $createdAt = null,
    ) {
    }

    public function toArray(): array
    {
        $gps = ($this->gpsLat !== null && $this->gpsLon !== null)
            ? sprintf('%s, %s (±%s m)', $this->gpsLat, $this->gpsLon, $this->gpsPrecision !== null ? round((float)$this->gpsPrecision) : '?')
            : '';

        return [
            'id' => $this->id,
            'usuarioId' => $this->usuarioId,
            'registradoPor' => $this->registradoPor,
            'fechaInforme' => $this->fechaInforme,
            'monitoreoInicio' => $this->monitoreoInicio ?? '',
            'monitoreoFin' => $this->monitoreoFin ?? '',
            'semana' => $this->semana,
            'anioSemana' => $this->anioSemana,
            'tipoInfraestructura' => $this->tipoInfraestructura,
            'tipoOtra' => $this->tipoOtra ?? '',
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'centroPoblado' => $this->centroPoblado,
            'gps' => $gps,
            'gpsLat' => $this->gpsLat,
            'gpsLon' => $this->gpsLon,
            'gpsPrecision' => $this->gpsPrecision,
            'eventoReportado' => $this->eventoReportado ?? '',
            'causa' => $this->causa,
            'causaOtra' => $this->causaOtra ?? '',
            'famAfectadas' => $this->famAfectadas,
            'perdidaEconomica' => $this->perdidaEconomica,
            'notas' => $this->notas ?? '',
            'creadoEn' => $this->createdAt,
        ];
    }
}
