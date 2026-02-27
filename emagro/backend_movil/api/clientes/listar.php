<?php
/**
 * API para listar clientes
 * Endpoint: GET /api/clientes/listar.php
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

    // Consultar clientes con información del usuario que los creó
    $query = "SELECT c.*, u.nombre as usuario_creador 
              FROM clientes c
              LEFT JOIN usuarios u ON c.usuario_id = u.id
              ORDER BY c.id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $clientes = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Clientes obtenidos correctamente',
        'data' => $clientes,
        'total' => count($clientes)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>