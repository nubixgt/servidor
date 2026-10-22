<?php
namespace App\Entities;

class DanoPecuario
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
        public string $departamento = '',
        public string $municipio = '',
        public string $centroPoblado = '',
        public string $especie = '',
        public ?string $especieOtra = null,
        public ?string $eventoReportado = null,
        public string $causa = '',
        public ?string $causaOtra = null,
        public ?int $famAfectadas = null,
        public ?int $animalesPerdidos = null,
        public $perdidaEconomica = null,
        public ?string $notas = null,
        public ?string $createdAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuarioId' => $this->usuarioId,
            'registradoPor' => $this->registradoPor,
            'fechaInforme' => $this->fechaInforme,
            'monitoreoInicio' => $this->monitoreoInicio ?? '',
            'monitoreoFin' => $this->monitoreoFin ?? '',
            'semana' => $this->semana,
            'anioSemana' => $this->anioSemana,
            'departamento' => $this->departamento,
            'municipio' => $this->municipio,
            'centroPoblado' => $this->centroPoblado,
            'especie' => $this->especie,
            'especieOtra' => $this->especieOtra ?? '',
            'eventoReportado' => $this->eventoReportado ?? '',
            'causa' => $this->causa,
            'causaOtra' => $this->causaOtra ?? '',
            'famAfectadas' => $this->famAfectadas,
            'animalesPerdidos' => $this->animalesPerdidos,
            'perdidaEconomica' => $this->perdidaEconomica,
            'notas' => $this->notas ?? '',
            'creadoEn' => $this->createdAt,
        ];
    }
}
