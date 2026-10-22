<?php
namespace App\DTOs;

class DanoInfraestructuraDTO
{
    public array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public static function fromRequest(array $data): self
    {
        return new self([
            'fechaInforme' => self::str($data['fechaInforme'] ?? ''),
            'monitoreoInicio' => self::str($data['monitoreoInicio'] ?? '') ?: null,
            'monitoreoFin' => self::str($data['monitoreoFin'] ?? '') ?: null,
            'tipoInfraestructura' => self::str($data['tipoInfraestructura'] ?? ''),
            'tipoOtra' => self::str($data['tipoOtra'] ?? ''),
            'departamento' => self::str($data['departamento'] ?? ''),
            'municipio' => self::str($data['municipio'] ?? ''),
            'centroPoblado' => self::str($data['centroPoblado'] ?? ''),
            'gpsLat' => self::num($data['gpsLat'] ?? null),
            'gpsLon' => self::num($data['gpsLon'] ?? null),
            'gpsPrecision' => self::num($data['gpsPrecision'] ?? null),
            'eventoReportado' => self::str($data['eventoReportado'] ?? '') ?: null,
            'causa' => self::str($data['causa'] ?? ''),
            'causaOtra' => self::str($data['causaOtra'] ?? ''),
            'famAfectadas' => self::num($data['famAfectadas'] ?? null),
            'perdidaEconomica' => self::num($data['perdidaEconomica'] ?? null),
            'notas' => self::str($data['notas'] ?? '') ?: null,
        ]);
    }

    private static function str($v): string
    {
        return is_scalar($v) ? trim((string)$v) : '';
    }

    private static function num($v): ?float
    {
        return ($v === '' || $v === null || !is_numeric($v)) ? null : (float)$v;
    }
}
