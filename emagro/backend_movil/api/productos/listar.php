<?php
/**
 * API para listar productos únicos
 * Endpoint: GET /api/productos/listar.php
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

    // Obtener productos únicos
    $query = "SELECT DISTINCT producto FROM productos_precios ORDER BY producto";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $productos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Productos obtenidos correctamente',
        'data' => $productos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>