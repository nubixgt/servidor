<?php
/**
 * API para listar ventas
 * Endpoint: GET /api/ventas/listar.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Consultar ventas con información del cliente y usuario
    $query = "SELECT v.*, 
                     c.nombre as cliente_nombre,
                     u.nombre as usuario_nombre
              FROM nueva_venta v
              LEFT JOIN clientes c ON v.cliente_id = c.id
              LEFT JOIN usuarios u ON v.usuario_id = u.id
              ORDER BY v.id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $ventas = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Ventas obtenidas correctamente',
        'data' => $ventas,
        'total' => count($ventas)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>