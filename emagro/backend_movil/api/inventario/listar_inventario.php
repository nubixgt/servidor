<?php
/**
 * API para listar inventario de productos
 * Endpoint: GET /api/inventario/listar_inventario.php
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

    // Obtener todos los productos con sus cantidades
    $query = "SELECT id, producto, presentacion, precio, cantidad 
              FROM productos_precios 
              ORDER BY producto, presentacion";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Inventario obtenido correctamente',
        'data' => $inventario,
        'total' => count($inventario)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
