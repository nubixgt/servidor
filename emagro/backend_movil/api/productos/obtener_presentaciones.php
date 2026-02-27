<?php
/**
 * API para obtener presentaciones y precios de un producto
 * Endpoint: GET /api/productos/obtener_presentaciones.php?producto=EM1
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

// Validar parámetro
if (empty($_GET['producto'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El parámetro "producto" es requerido'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener presentaciones y precios del producto
    $query = "SELECT presentacion, precio, cantidad 
              FROM productos_precios 
              WHERE producto = :producto 
              ORDER BY precio ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':producto', $_GET['producto']);
    $stmt->execute();

    $presentaciones = $stmt->fetchAll();

    if (count($presentaciones) === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron presentaciones para este producto'
        ]);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Presentaciones obtenidas correctamente',
        'data' => $presentaciones
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>