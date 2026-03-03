<?php
/**
 * API: Obtener datos para el mapa
 * VIDER - MAGA Guatemala
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    $db = Database::getInstance();

    // Obtener datos por departamento
    $departamentos = $db->fetchAll("
        SELECT 
            d.nombre as departamento,
            d.codigo,
            d.coordenadas_lat,
            d.coordenadas_lng,
            COALESCE(SUM(dv.total_personas), 0) as total_beneficiarios,
            COALESCE(SUM(dv.hombres), 0) as total_hombres,
            COALESCE(SUM(dv.mujeres), 0) as total_mujeres,
            COALESCE(SUM(dv.programado), 0) as total_programado,
            COALESCE(SUM(dv.ejecutado), 0) as total_ejecutado,
            COALESCE(ROUND(
                CASE WHEN SUM(dv.programado) > 0 
                    THEN (SUM(dv.ejecutado) / SUM(dv.programado)) * 100 
                    ELSE 0 
                END, 2
            ), 0) as porcentaje_ejecucion,
            COALESCE(SUM(dv.vigente_financiera), 0) as total_financiero_vigente,
            COALESCE(SUM(dv.financiera_ejecutado), 0) as total_financiero_ejecutado,
            COUNT(DISTINCT dv.municipio_id) as municipios_con_datos,
            COUNT(dv.id) as total_registros
        FROM departamentos d
        LEFT JOIN datos_vider dv ON d.id = dv.departamento_id
        GROUP BY d.id, d.nombre, d.codigo, d.coordenadas_lat, d.coordenadas_lng
        ORDER BY d.nombre
    ");

    // Convertir a array con formato correcto para el mapa
    $data = [];
    foreach ($departamentos as $dept) {
        $data[] = [
            'departamento' => $dept['departamento'],
            'codigo' => $dept['codigo'],
            'lat' => floatval($dept['coordenadas_lat']),
            'lng' => floatval($dept['coordenadas_lng']),
            'total' => intval($dept['total_beneficiarios']),
            'beneficiarios' => intval($dept['total_beneficiarios']),
            'hombres' => intval($dept['total_hombres']),
            'mujeres' => intval($dept['total_mujeres']),
            'total_programado' => floatval($dept['total_programado']),
            'total_ejecutado' => floatval($dept['total_ejecutado']),
            'porcentaje_ejecucion' => floatval($dept['porcentaje_ejecucion']),
            'total_financiero_vigente' => floatval($dept['total_financiero_vigente']),
            'total_financiero_ejecutado' => floatval($dept['total_financiero_ejecutado']),
            'municipios_con_datos' => intval($dept['municipios_con_datos']),
            'total_registros' => intval($dept['total_registros'])
        ];
    }

    jsonResponse([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    logError('Error en get_map_data: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener datos del mapa'
    ], 500);
}
