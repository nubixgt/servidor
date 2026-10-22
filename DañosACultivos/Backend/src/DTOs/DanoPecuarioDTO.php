<?php
namespace App\DTOs;

class DanoPecuarioDTO
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
            'departamento' => self::str($data['departamento'] ?? ''),
            'municipio' => self::str($data['municipio'] ?? ''),
            'centroPoblado' => self::str($data['centroPoblado'] ?? ''),
            'especie' => self::str($data['especie'] ?? ''),
            'especieOtra' => self::str($data['especieOtra'] ?? ''),
            'eventoReportado' => self::str($data['eventoReportado'] ?? '') ?: null,
            'causa' => self::str($data['causa'] ?? ''),
            'causaOtra' => self::str($data['causaOtra'] ?? ''),
            'famAfectadas' => self::num($data['famAfectadas'] ?? null),
            'animalesPerdidos' => self::num($data['animalesPerdidos'] ?? null),
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
