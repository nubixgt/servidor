<?php
namespace App\DTOs;

class ParcelaDTO
{
    /** Campos que se pueden crear/editar desde el formulario. */
    public const EDITABLE_FIELDS = [
        'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'tenenciaTierra',
        'numFamiliasBeneficiadas', 'fechaRegistro', 'latitud', 'longitud', 'gpsPrecision', 'poligono', 'altitud',
        'areaHa', 'estado', 'usoActual', 'claseTextural', 'pendiente', 'fuenteAguaPrincipal', 'fuenteAguaSecundaria',
        'sistemaRiego', 'riesgoErosion', 'cultivoPrincipal', 'profundidadSuelo', 'encharca', 'limitantesUso',
        'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones', 'especiesReforestacion',
        'fechaProximaVisita', 'consentimientoProductor', 'observaciones',
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
            } elseif ($key === 'encharca') {
                $fields[$key] = in_array($value, ['Sí', 'No'], true) ? $value : '';
            } elseif ($key === 'poligono') {
                $fields[$key] = self::safePoligono($value);
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

    /**
     * Normaliza el contorno de la parcela: acepta un arreglo o su JSON y devuelve
     * un JSON con la forma [[lat,lng], ...]. Cualquier cosa inválida => '' (NULL en BD).
     */
    private static function safePoligono($value): string
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || $value === '[]') {
                return '';
            }
            $value = json_decode($value, true);
        }
        if (!is_array($value)) {
            return '';
        }
        $puntos = [];
        foreach ($value as $par) {
            if (is_array($par) && count($par) === 2 && is_numeric($par[0]) && is_numeric($par[1])) {
                $puntos[] = [round((float)$par[0], 6), round((float)$par[1], 6)];
            }
        }
        return count($puntos) >= 3 ? json_encode($puntos) : '';
    }
}
