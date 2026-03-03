<?php
/**
 * API: Obtener catálogos para filtros del Dashboard
 * VIDER 2025 - MAGA Guatemala
 * Soporta filtrado en cascada por dependencia
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    $db = Database::getInstance();

    // Obtener parámetro de dependencia para filtrado en cascada
    $dependenciaIds = isset($_GET['dependencia']) ? $_GET['dependencia'] : '';
    
    // Construir cláusula WHERE para dependencia
    $dependenciaWhere = '';
    $dependenciaParams = [];
    
    if (!empty($dependenciaIds)) {
        $ids = array_map('intval', explode(',', $dependenciaIds));
        $ids = array_filter($ids);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $dependenciaWhere = " AND dv.dependencia_id IN ($placeholders)";
            $dependenciaParams = $ids;
        }
    }

    // Obtener dependencias con datos (siempre sin filtro)
    $dependencias = $db->fetchAll("
        SELECT DISTINCT dep.id, dep.nombre, dep.siglas
        FROM dependencias dep
        INNER JOIN datos_vider dv ON dep.id = dv.dependencia_id
        ORDER BY dep.nombre
    ");

    // Obtener actividades con datos (filtrado por dependencia si se especifica)
    $sqlActividades = "
        SELECT DISTINCT a.id, a.nombre
        FROM actividades a
        INNER JOIN datos_vider dv ON a.id = dv.actividad_id
        WHERE 1=1 $dependenciaWhere
        ORDER BY a.nombre
    ";
    $actividades = $db->fetchAll($sqlActividades, $dependenciaParams);

    // Obtener productos con datos (filtrado por dependencia si se especifica)
    $sqlProductos = "
        SELECT DISTINCT p.id, p.nombre
        FROM productos p
        INNER JOIN datos_vider dv ON p.id = dv.producto_id
        WHERE 1=1 $dependenciaWhere
        ORDER BY p.nombre
    ";
    $productos = $db->fetchAll($sqlProductos, $dependenciaParams);

    // Obtener intervenciones con datos (filtrado por dependencia si se especifica)
    $sqlIntervenciones = "
        SELECT DISTINCT i.id, i.nombre
        FROM intervenciones i
        INNER JOIN datos_vider dv ON i.id = dv.intervencion_id
        WHERE 1=1 $dependenciaWhere
        ORDER BY i.nombre
    ";
    $intervenciones = $db->fetchAll($sqlIntervenciones, $dependenciaParams);

    // Obtener departamentos con datos
    $departamentos = $db->fetchAll("
        SELECT DISTINCT d.id, d.nombre
        FROM departamentos d
        INNER JOIN datos_vider dv ON d.id = dv.departamento_id
        ORDER BY d.nombre
    ");

    jsonResponse([
        'success' => true,
        'data' => [
            'dependencias' => $dependencias ?: [],
            'actividades' => $actividades ?: [],
            'productos' => $productos ?: [],
            'intervenciones' => $intervenciones ?: [],
            'departamentos' => $departamentos ?: []
        ],
        'filtered_by' => !empty($dependenciaIds) ? 'dependencia' : null
    ]);

} catch (Exception $e) {
    logError('Error en get_filter_catalogs: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'error' => 'Error al obtener catálogos: ' . $e->getMessage(),
        'data' => [
            'dependencias' => [],
            'actividades' => [],
            'productos' => [],
            'intervenciones' => [],
            'departamentos' => []
        ]
    ]);
}