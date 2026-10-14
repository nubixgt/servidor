<?php
namespace App\DTOs;

class DanoCultivoDTO
{
    public array $fields;
    /** @var array<int, array{cultivo:string,etapa:?string,areaLote:?float,areaAfectada:?float}> */
    public array $asociados;

    public function __construct(array $fields, array $asociados = [])
    {
        $this->fields = $fields;
        $this->asociados = $asociados;
    }

    public static function fromRequest(array $data): self
    {
        $fields = [
            'fecha' => self::str($data['fecha'] ?? ''),
            'responsable' => self::str($data['tecnico'] ?? ''),
            'departamento' => self::str($data['departamento'] ?? ''),
            'municipio' => self::str($data['municipio'] ?? ''),
            'centroPoblado' => self::str($data['centroPoblado'] ?? ''),
            'agricultorRua' => filter_var($data['agricultorRua'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'codigoRua' => self::str($data['codigoRua'] ?? ''),
            'lote' => self::str($data['lote'] ?? ''),
            'cultivo' => self::str($data['cultivo'] ?? ''),
            'etapa' => self::str($data['etapa'] ?? '') ?: null,
            'causa' => self::str($data['causa'] ?? ''),
            'causaOtra' => self::str($data['causaOtra'] ?? ''),
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

        $asociados = [];
        foreach (is_array($data['asociados'] ?? null) ? $data['asociados'] : [] as $a) {
            if (!is_array($a)) {
                continue;
            }
            $asociados[] = [
                'cultivo' => self::str($a['cultivo'] ?? ''),
                'etapa' => self::str($a['etapa'] ?? '') ?: null,
                'areaLote' => self::num($a['areaLote'] ?? null),
                'areaAfectada' => self::num($a['areaAfectada'] ?? null),
            ];
        }

        return new self($fields, $asociados);
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
