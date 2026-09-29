<?php
namespace App\DTOs;

class DanoCultivoDTO
{
    public array $fields;
    /** @var array<int, array{ref:string,incidencia:?float,severidad:?int,obs:string}> */
    public array $puntos;

    public function __construct(array $fields, array $puntos)
    {
        $this->fields = $fields;
        $this->puntos = $puntos;
    }

    public static function fromRequest(array $data): self
    {
        $fields = [
            'fecha' => self::str($data['fecha'] ?? ''),
            'responsable' => self::str($data['tecnico'] ?? ''),
            'lote' => self::str($data['lote'] ?? ''),
            'cultivo' => self::str($data['cultivo'] ?? ''),
            'etapa' => self::str($data['etapa'] ?? '') ?: null,
            'causa' => self::str($data['causa'] ?? ''),
            'areaLote' => self::num($data['areaLote'] ?? null),
            'areaAfectada' => self::num($data['areaAfectada'] ?? null),
            'danoPorc' => self::num($data['danoPorc'] ?? null),
            'rendEsperado' => self::num($data['rendEsperado'] ?? null),
            'unidad' => self::str($data['unidad'] ?? 't/ha'),
            'perdidaEstimada' => self::num($data['perdidaEstimada'] ?? null),
            'gpsLat' => self::num($data['gpsLat'] ?? null),
            'gpsLon' => self::num($data['gpsLon'] ?? null),
            'gpsPrecision' => self::num($data['gpsPrecision'] ?? null),
            'notas' => self::str($data['notas'] ?? '') ?: null,
        ];

        $puntos = [];
        foreach (is_array($data['puntos'] ?? null) ? $data['puntos'] : [] as $p) {
            if (!is_array($p)) {
                continue;
            }
            $sev = $p['severidad'] ?? '';
            $puntos[] = [
                'ref' => self::str($p['ref'] ?? ''),
                'incidencia' => self::num($p['incidencia'] ?? null),
                'severidad' => ($sev === '' || $sev === null || !is_numeric($sev)) ? null : (int)$sev,
                'obs' => self::str($p['obs'] ?? ''),
            ];
        }

        return new self($fields, $puntos);
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
