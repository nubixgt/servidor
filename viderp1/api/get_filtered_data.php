<?php
/**
 * API: Obtener datos filtrados
 * VIDER - MAGA Guatemala
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    $db = Database::getInstance();
    
    // Obtener datos con joins
    $data = $db->fetchAll("
        SELECT 
            dv.id,
            dv.programado,
            dv.ejecutado,
            dv.porcentaje_ejecucion,
            dv.hombres,
            dv.mujeres,
            dv.total_personas,
            dv.beneficiarios,
            dv.vigente_financiera,
            dv.financiera_ejecutado,
            dv.financiera_porcentaje,
            d.id as departamento_id,
            d.nombre as departamento,
            m.id as municipio_id,
            m.nombre as municipio,
            dep.id as dependencia_id,
            dep.nombre as dependencia,
            dep.siglas,
            p.id as producto_id,
            p.nombre as producto
        FROM datos_vider dv
        LEFT JOIN departamentos d ON dv.departamento_id = d.id
        LEFT JOIN municipios m ON dv.municipio_id = m.id
        LEFT JOIN dependencias dep ON dv.dependencia_id = dep.id
        LEFT JOIN productos p ON dv.producto_id = p.id
        ORDER BY d.nombre, m.nombre
    ");
    
    // Estadísticas generales
    $stats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_registros,
            COALESCE(SUM(total_personas), 0) as total_beneficiarios,
            COUNT(DISTINCT departamento_id) as total_departamentos,
            COUNT(DISTINCT municipio_id) as total_municipios
        FROM datos_vider
    ");
    
    jsonResponse([
        'success' => true,
        'data' => $data,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    logError('Error en get_filtered_data: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener datos: ' . $e->getMessage()
    ], 500);
}
