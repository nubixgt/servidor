<?php
/**
 * API RESUMEN - SISTEMA DE VOTACIONES DEL CONGRESO
 * 
 * Endpoint público para obtener estadísticas del sistema
 * Usado por el Dashboard Central de MAGA
 * 
 * NO requiere autenticación (datos públicos de resumen)
 */

// Headers CORS para permitir peticiones desde el dashboard central
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => true, 'mensaje' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/config.php';

try {
    $db = getDB();
    
    // Total de eventos registrados
    $stmt = $db->query("SELECT COUNT(*) as total FROM eventos_votacion");
    $total_eventos = $stmt->fetch()['total'];
    
    // Total de congresistas activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM congresistas WHERE activo = 1");
    $total_congresistas = $stmt->fetch()['total'];
    
    // Total de bloques políticos activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM bloques WHERE activo = 1");
    $total_bloques = $stmt->fetch()['total'];
    
    // Total de votos registrados
    $stmt = $db->query("SELECT COUNT(*) as total FROM votos");
    $total_votos = $stmt->fetch()['total'];
    
    // Respuesta en el formato que espera el dashboard
    $resumen = [
        'eventos_registrados' => (int)$total_eventos,
        'congresistas' => (int)$total_congresistas,
        'bloques_politicos' => (int)$total_bloques,
        'votos_registrados' => (int)$total_votos
    ];
    
    http_response_code(200);
    echo json_encode($resumen, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en api-resumen.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al obtener datos del resumen'
    ]);
}
?>