<?php
namespace App\Services;

use App\Core\HttpException;

/**
 * Catálogo de columnas disponibles para los reportes en Excel (geográfico y por DPI).
 * Todas salen de la vista core_v_expediente (alias v) y de los catálogos unidos por llave primaria,
 * por lo que ninguna combinación duplica filas. No se inventan campos: lo que no existe en la base no aparece.
 */
class ReporteColumnas
{
    public const CATEGORIAS = [
        'identificacion' => 'Identificación del beneficiario',
        'personal' => 'Información personal',
        'ubicacion' => 'Ubicación geográfica',
        'solicitudes' => 'Solicitudes y visitas',
        'resoluciones' => 'Resoluciones',
        'estados' => 'Estados administrativos',
        'nomina' => 'Información de nómina',
        'expediente' => 'Datos del expediente',
    ];

    private const SI_NO = "CASE %s WHEN 1 THEN 'Sí' WHEN 0 THEN 'No' END";

    private const JOINS = [
        'vis' => 'LEFT JOIN ref_cat_resultado_visita vis ON vis.id = v.ult_visita_resultado_id',
        'tr' => 'LEFT JOIN ref_cat_tipo_resolucion tr ON tr.id = v.ult_resolucion_tipo_id',
        'fam' => 'LEFT JOIN ref_cat_familia_resolucion fam ON fam.id = v.ult_resolucion_familia_id',
        'apo' => 'LEFT JOIN core_apoderado apo ON apo.id = v.apoderado_id',
    ];

    /** id => [categoría, etiqueta (título del encabezado), expresión SQL, unión necesaria] */
    private static function definicion(): array
    {
        return [
            'nombre' => ['identificacion', 'Nombre completo', 'v.nombre', null],
            'dpi' => ['identificacion', 'DPI', 'v.documento_numero', null],
            'documento_tipo' => ['identificacion', 'Tipo de documento', 'v.documento_tipo', null],
            'cui_valido' => ['identificacion', 'CUI válido', sprintf(self::SI_NO, 'v.cui_valido'), null],

            'fecha_nacimiento' => ['personal', 'Fecha de nacimiento', 'v.fecha_nacimiento', null],
            'edad' => ['personal', 'Edad', 'v.edad', null],
            'telefono' => ['personal', 'Teléfono', 'v.telefono', null],
            'direccion' => ['personal', 'Dirección', 'v.direccion', null],
            'apoderado_nombre' => ['personal', 'Apoderado', 'apo.nombre', 'apo'],
            'apoderado_documento' => ['personal', 'Documento del apoderado', 'apo.numero_id', 'apo'],

            'departamento_codigo' => ['ubicacion', 'Código de departamento', 'v.departamento_id', null],
            'departamento' => ['ubicacion', 'Departamento', 'v.departamento', null],
            'municipio_codigo' => ['ubicacion', 'Código de municipio', 'v.municipio_id', null],
            'municipio' => ['ubicacion', 'Municipio', 'v.municipio', null],

            'fecha_solicitud' => ['solicitudes', 'Fecha de solicitud', 'v.fecha_solicitud', null],
            'ult_visita_fecha' => ['solicitudes', 'Fecha de última visita', 'v.ult_visita_fecha', null],
            'ult_visita_resultado' => ['solicitudes', 'Resultado de última visita', 'vis.nombre', 'vis'],

            'ult_resolucion_codigo' => ['resoluciones', 'Código de última resolución', 'v.ult_resolucion_codigo', null],
            'ult_resolucion_nombre' => ['resoluciones', 'Última resolución', 'tr.nombre', 'tr'],
            'ult_resolucion_familia' => ['resoluciones', 'Familia de resolución', 'fam.nombre', 'fam'],
            'ult_resolucion_fecha' => ['resoluciones', 'Fecha de última resolución', 'v.ult_resolucion_fecha', null],

            'estado' => ['estados', 'Estado del expediente', 'v.estado', null],
            'fecha_estado' => ['estados', 'Fecha del estado', 'v.fecha_estado', null],
            'fecha_suspension' => ['estados', 'Fecha de suspensión', 'v.fecha_suspension', null],
            'fecha_fallecimiento' => ['estados', 'Fecha de fallecimiento', 'v.fecha_fallecimiento', null],

            'situacion_nomina' => ['nomina', 'Situación en nómina',
                "CASE v.situacion_nomina WHEN 1 THEN 'Activo' WHEN 2 THEN 'Egresado' ELSE 'Nunca ingresó' END", null],
            'fecha_nomina_desde' => ['nomina', 'Fecha de ingreso a nómina', 'v.fecha_nomina_desde', null],
            'fecha_nomina_hasta' => ['nomina', 'Fecha de salida de nómina', 'v.fecha_nomina_hasta', null],

            'numero' => ['expediente', 'Número de expediente', 'v.numero', null],
            'ult_observacion' => ['expediente', 'Última observación', 'v.ult_observacion', null],
            'ult_observacion_fecha' => ['expediente', 'Fecha de última observación', 'v.ult_observacion_fecha', null],
            'dpi_duplicado' => ['expediente', 'DPI repetido en otro expediente', sprintf(self::SI_NO, 'v.flag_dpi_duplicado'), null],
            'expediente_duplicado' => ['expediente', 'Número de expediente repetido', sprintf(self::SI_NO, 'v.flag_exp_duplicado'), null],
            'actualizado_en' => ['expediente', 'Última actualización del registro', 'v.actualizado_en', null],
        ];
    }

    /** Selección inicial sugerida a la interfaz. */
    public const PREDETERMINADAS = ['numero', 'nombre', 'dpi', 'departamento', 'municipio', 'estado', 'fecha_solicitud'];

    /** Catálogo agrupado por categoría, tal como lo consume la interfaz. */
    public static function catalogo(): array
    {
        $porCat = [];
        foreach (self::definicion() as $id => [$cat, $etiqueta]) {
            $porCat[$cat][] = ['id' => $id, 'etiqueta' => $etiqueta];
        }
        $out = [];
        foreach (self::CATEGORIAS as $id => $titulo) {
            if (!empty($porCat[$id])) {
                $out[] = ['id' => $id, 'titulo' => $titulo, 'columnas' => $porCat[$id]];
            }
        }
        return $out;
    }

    /**
     * Valida la selección (respeta el orden pedido, sin repetidos).
     * @return string[]
     */
    public static function validar($ids): array
    {
        $def = self::definicion();
        $ok = [];
        foreach (is_array($ids) ? $ids : [] as $id) {
            if (!is_string($id) || !isset($def[$id])) {
                throw new HttpException('Columna desconocida: ' . (is_string($id) ? $id : '?'), 400);
            }
            $ok[$id] = true;
        }
        if (!$ok) {
            throw new HttpException('Seleccione al menos una columna para el reporte', 400);
        }
        return array_keys($ok);
    }

    /** @return string[] encabezados en el mismo orden que $ids */
    public static function etiquetas(array $ids): array
    {
        $def = self::definicion();
        return array_map(fn($id) => $def[$id][1], $ids);
    }

    /**
     * SELECT y uniones para las columnas elegidas.
     * @return array{0: string, 1: string} [lista de columnas, uniones]
     */
    public static function sql(array $ids): array
    {
        $def = self::definicion();
        $sel = [];
        $joins = [];
        foreach ($ids as $i => $id) {
            $sel[] = $def[$id][2] . ' AS c' . $i;
            if ($def[$id][3]) {
                $joins[$def[$id][3]] = self::JOINS[$def[$id][3]];
            }
        }
        return [implode(', ', $sel), implode(' ', $joins)];
    }

    /** Anchos de columna aproximados para el Excel. */
    public static function anchos(array $ids): array
    {
        return array_map(function ($id) {
            return match (true) {
                in_array($id, ['nombre', 'direccion', 'ult_observacion', 'apoderado_nombre', 'ult_resolucion_nombre'], true) => 40,
                in_array($id, ['dpi', 'documento_tipo', 'apoderado_documento'], true) => 18,
                str_starts_with($id, 'fecha_') || str_starts_with($id, 'ult_') => 22,
                in_array($id, ['edad', 'cui_valido', 'departamento_codigo', 'municipio_codigo'], true) => 12,
                default => 26,
            };
        }, $ids);
    }
}
