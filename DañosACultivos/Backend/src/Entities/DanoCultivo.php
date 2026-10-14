<?php
namespace App\Entities;

class DanoCultivo
{
    public function __construct(
        public ?int $id = null,
        public ?int $usuarioId = null,
        public ?string $registradoPor = null,
        public string $fecha = '',
        public string $responsable = '',
        public ?string $departamento = null,
        public ?string $municipio = null,
        public ?string $centroPoblado = null,
        public bool $agricultorRua = false,
        public ?string $codigoRua = null,
        public string $lote = '',
        public string $cultivo = '',
        public ?string $etapa = null,
        public string $causa = '',
        public ?string $causaOtra = null,
        public $areaLote = null,
        public $areaAfectada = 0,
        public $danoPorc = 0,
        public $rendEsperado = null,
        public string $unidad = 't/ha',
        public $perdidaEstimada = null,
        public $gpsLat = null,
        public $gpsLon = null,
        public $gpsPrecision = null,
        public ?string $notas = null,
        public ?string $createdAt = null,
        /** @var array<int, array> */
        public array $fotos = [],
        /** @var array<int, array{cultivo:string,etapa:string,areaLote:mixed,areaAfectada:mixed}> */
        public array $asociados = [],
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
            'fecha' => $this->fecha,
            'tecnico' => $this->responsable,
            'departamento' => $this->departamento ?? '',
            'municipio' => $this->municipio ?? '',
            'centroPoblado' => $this->centroPoblado ?? '',
            'agricultorRua' => $this->agricultorRua,
            'codigoRua' => $this->codigoRua ?? '',
            'lote' => $this->lote,
            'cultivo' => $this->cultivo,
            'etapa' => $this->etapa ?? '',
            'causa' => $this->causa,
            'causaOtra' => $this->causaOtra ?? '',
            'asociados' => $this->asociados,
            'areaLote' => $this->areaLote,
            'areaAfectada' => $this->areaAfectada,
            'danoPorc' => $this->danoPorc,
            'rendEsperado' => $this->rendEsperado,
            'unidad' => $this->unidad,
            'perdidaEstimada' => $this->perdidaEstimada,
            'gps' => $gps,
            'gpsLat' => $this->gpsLat,
            'gpsLon' => $this->gpsLon,
            'gpsPrecision' => $this->gpsPrecision,
            'notas' => $this->notas ?? '',
            'creadoEn' => $this->createdAt,
            'fotos' => $this->fotos,
        ];
    }
}
