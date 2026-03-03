<?php
/**
 * API: Obtener municipios de un departamento
 * VIDER - MAGA Guatemala
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    $db = Database::getInstance();

    $departamento = isset($_GET['departamento']) ? sanitize($_GET['departamento']) : '';

    if (empty($departamento)) {
        jsonResponse([
            'success' => false,
            'message' => 'Departamento no especificado'
        ], 400);
    }

    // Obtener ID del departamento
    $dept = $db->fetchOne(
        "SELECT id FROM departamentos WHERE nombre = ?",
        [$departamento]
    );

    if (!$dept) {
        jsonResponse([
            'success' => false,
            'message' => 'Departamento no encontrado'
        ], 404);
    }

    // Obtener municipios con datos
    $municipios = $db->fetchAll("
        SELECT 
            m.nombre as municipio,
            m.codigo,
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
            ), 0) as porcentaje,
            COALESCE(SUM(dv.vigente_financiera), 0) as vigente_financiera,
            COALESCE(SUM(dv.financiera_ejecutado), 0) as financiera_ejecutado,
            COUNT(dv.id) as total_registros
        FROM municipios m
        LEFT JOIN datos_vider dv ON m.id = dv.municipio_id
        WHERE m.departamento_id = ?
        GROUP BY m.id, m.nombre, m.codigo
        ORDER BY total_beneficiarios DESC, m.nombre ASC
    ", [$dept['id']]);

    jsonResponse([
        'success' => true,
        'departamento' => $departamento,
        'data' => $municipios
    ]);

} catch (Exception $e) {
    logError('Error en get_municipios: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener municipios'
    ], 500);
}
