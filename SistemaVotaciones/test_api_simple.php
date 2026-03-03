<?php
// test_api_simple.php - ARCHIVO TEMPORAL SOLO PARA PRUEBAS
require_once 'config.php';

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
    
    // Votos por tipo
    $stmt = $db->query("
        SELECT 
            voto,
            COUNT(*) as total
        FROM votos
        GROUP BY voto
    ");
    $votos_por_tipo = [];
    while ($row = $stmt->fetch()) {
        $votos_por_tipo[] = [
            'tipo' => $row['voto'],
            'total' => (int)$row['total']
        ];
    }
    
    // Construir respuesta
    $resumen = [
        'sistema' => 'Sistema de Votaciones del Congreso',
        'icono' => 'fa-vote-yea',
        'color' => '#667eea',
        'fecha_actualizacion' => date('Y-m-d H:i:s'),
        'datos' => [
            'eventos_registrados' => (int)$total_eventos,
            'congresistas' => (int)$total_congresistas,
            'bloques_politicos' => (int)$total_bloques,
            'votos_registrados' => (int)$total_votos
        ],
        'votos_detalle' => $votos_por_tipo,
        'alertas' => 0
    ];
    
    header('Content-Type: application/json');
    echo json_encode($resumen, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al obtener datos',
        'detalle' => $e->getMessage()
    ]);
}
?>