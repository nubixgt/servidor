<?php
namespace App\DTOs;

class ParcelaDTO
{
    /** Campos que se pueden crear/editar desde el formulario. */
    public const EDITABLE_FIELDS = [
        'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'tenenciaTierra',
        'numFamiliasBeneficiadas', 'fechaRegistro', 'latitud', 'longitud', 'gpsPrecision', 'altitud', 'areaHa',
        'estado', 'usoActual', 'tipoSuelo', 'pendiente', 'agua', 'fuenteAgua', 'sistemaRiego', 'riesgoErosion',
        'cultivoPrincipal', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual',
        'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'fechaProximaVisita', 'consentimientoProductor',
        'observaciones',
    ];

    private const NUMERIC_FIELDS = [
        'numFamiliasBeneficiadas', 'latitud', 'longitud', 'gpsPrecision', 'altitud', 'areaHa',
        'pendiente', 'profundidadSuelo', 'lluviaAnual',
    ];

    public array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public static function fromRequest(array $data): self
    {
        $fields = [];
        foreach (self::EDITABLE_FIELDS as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($key === 'consentimientoProductor') {
                $fields[$key] = (bool)$value;
            } elseif (in_array($key, self::NUMERIC_FIELDS, true)) {
                $fields[$key] = self::safeNum($value);
            } elseif (in_array($key, ['talpetate', 'encharca'], true)) {
                $fields[$key] = in_array($value, ['Sí', 'No'], true) ? $value : '';
            } else {
                $fields[$key] = is_string($value) ? trim($value) : $value;
            }
        }
        return new self($fields);
    }

    private static function safeNum($value)
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return is_numeric($value) ? $value + 0 : '';
    }
}
