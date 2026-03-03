<?php
/**
 * API: Estadísticas de TOBANIK (Cooperativas)
 * VIDER - MAGA Guatemala
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    $db = Database::getInstance();

    // Filtro por departamento (opcional)
    $departamentoId = isset($_GET['departamento']) && $_GET['departamento'] !== '' 
        ? intval($_GET['departamento']) 
        : null;

    $whereClause = '';
    $params = [];

    if ($departamentoId) {
        $whereClause = " WHERE t.departamento_id = :departamento_id";
        $params[':departamento_id'] = $departamentoId;
    }

    // Estadísticas generales
    $sqlTotales = "
        SELECT 
            COUNT(*) as total_cooperativas,
            COALESCE(SUM(t.cantidad_productores), 0) as total_productores,
            COALESCE(SUM(t.monto_colocado), 0) as total_monto_colocado,
            COALESCE(SUM(t.monto_otorgado), 0) as total_monto_otorgado,
            COALESCE(SUM(t.monto_financiero), 0) as total_monto_financiero,
            COALESCE(AVG(t.monto_colocado), 0) as promedio_monto_colocado,
            COALESCE(AVG(t.cantidad_productores), 0) as promedio_productores
        FROM tobanik t
        $whereClause
    ";

    $totales = $db->fetchOne($sqlTotales, $params);

    // Top 10 cooperativas por monto colocado
    $sqlTopCooperativas = "
        SELECT 
            t.nombre_cooperativa,
            t.sede,
            t.monto_colocado,
            t.cantidad_productores,
            t.monto_otorgado,
            d.nombre as departamento
        FROM tobanik t
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        $whereClause
        ORDER BY t.monto_colocado DESC
        LIMIT 10
    ";

    $topCooperativas = $db->fetchAll($sqlTopCooperativas, $params);

    // Distribución por departamento
    $sqlPorDepartamento = "
        SELECT 
            d.nombre as departamento,
            COUNT(t.id) as cantidad_cooperativas,
            COALESCE(SUM(t.cantidad_productores), 0) as total_productores,
            COALESCE(SUM(t.monto_colocado), 0) as monto_colocado,
            COALESCE(SUM(t.monto_otorgado), 0) as monto_otorgado
        FROM tobanik t
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        WHERE d.nombre IS NOT NULL
        GROUP BY d.id, d.nombre
        ORDER BY monto_colocado DESC
    ";

    $porDepartamento = $db->fetchAll($sqlPorDepartamento);

    // Todas las cooperativas (para la tabla)
    $sqlTodasCooperativas = "
        SELECT 
            t.id,
            t.nombre_cooperativa,
            t.sede,
            t.monto_colocado,
            t.cantidad_productores,
            t.monto_otorgado,
            t.monto_financiero,
            t.cantidad_productores_depto,
            d.nombre as departamento,
            t.created_at
        FROM tobanik t
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        $whereClause
        ORDER BY t.nombre_cooperativa ASC
    ";

    $todasCooperativas = $db->fetchAll($sqlTodasCooperativas, $params);

    // Distribución de productores (para gráfico de dona)
    $sqlDistribucionProductores = "
        SELECT 
            d.nombre as departamento,
            COALESCE(SUM(t.cantidad_productores), 0) as productores
        FROM tobanik t
        LEFT JOIN departamentos d ON t.departamento_id = d.id
        WHERE d.nombre IS NOT NULL AND t.cantidad_productores > 0
        GROUP BY d.id, d.nombre
        ORDER BY productores DESC
        LIMIT 8
    ";

    $distribucionProductores = $db->fetchAll($sqlDistribucionProductores);

    // Cooperativas por rango de monto
    $sqlRangoMontos = "
        SELECT 
            CASE 
                WHEN monto_colocado = 0 THEN 'Sin monto'
                WHEN monto_colocado < 100000 THEN 'Menor a Q100K'
                WHEN monto_colocado < 500000 THEN 'Q100K - Q500K'
                WHEN monto_colocado < 1000000 THEN 'Q500K - Q1M'
                WHEN monto_colocado < 5000000 THEN 'Q1M - Q5M'
                ELSE 'Mayor a Q5M'
            END as rango,
            COUNT(*) as cantidad
        FROM tobanik t
        $whereClause
        GROUP BY rango
        ORDER BY 
            CASE rango
                WHEN 'Sin monto' THEN 1
                WHEN 'Menor a Q100K' THEN 2
                WHEN 'Q100K - Q500K' THEN 3
                WHEN 'Q500K - Q1M' THEN 4
                WHEN 'Q1M - Q5M' THEN 5
                ELSE 6
            END
    ";

    $rangoMontos = $db->fetchAll($sqlRangoMontos, $params);

    jsonResponse([
        'success' => true,
        'totales' => $totales,
        'topCooperativas' => $topCooperativas,
        'porDepartamento' => $porDepartamento,
        'todasCooperativas' => $todasCooperativas,
        'distribucionProductores' => $distribucionProductores,
        'rangoMontos' => $rangoMontos
    ]);

} catch (Exception $e) {
    logError('Error en get_tobanik_stats: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ], 500);
}