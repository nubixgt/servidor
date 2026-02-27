<?php
/**
 * API para actualizar producto-precio
 * Endpoint: PUT /api/productos/actualizar.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

// Solo permitir PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener datos JSON
$data = json_decode(file_get_contents("php://input"));

// Validar datos requeridos
if (empty($data->id) || empty($data->producto) || empty($data->presentacion) || !isset($data->precio) || !isset($data->cantidad)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Todos los campos son requeridos'
    ]);
    exit();
}

// Validar que el precio sea numérico y mayor a 0
if (!is_numeric($data->precio) || $data->precio < 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El precio debe ser un número mayor o igual a 0'
    ]);
    exit();
}

// Validar que la cantidad sea numérica y mayor o igual a 0
if (!is_numeric($data->cantidad) || $data->cantidad < 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'La cantidad debe ser un número entero mayor o igual a 0'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el producto existe
    $checkQuery = "SELECT id FROM productos_precios WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data->id);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
        exit();
    }

    // Verificar si ya existe otra combinación producto-presentación
    $duplicateQuery = "SELECT id FROM productos_precios 
                       WHERE producto = :producto 
                       AND presentacion = :presentacion 
                       AND id != :id";
    $duplicateStmt = $db->prepare($duplicateQuery);
    $duplicateStmt->bindParam(':producto', $data->producto);
    $duplicateStmt->bindParam(':presentacion', $data->presentacion);
    $duplicateStmt->bindParam(':id', $data->id);
    $duplicateStmt->execute();

    if ($duplicateStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe otro producto con esta presentación'
        ]);
        exit();
    }

    // Actualizar producto-precio
    $query = "UPDATE productos_precios 
              SET producto = :producto, 
                  presentacion = :presentacion, 
                  precio = :precio,
                  cantidad = :cantidad 
              WHERE id = :id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':producto', $data->producto);
    $stmt->bindParam(':presentacion', $data->presentacion);
    $stmt->bindParam(':precio', $data->precio);
    $stmt->bindParam(':cantidad', $data->cantidad, PDO::PARAM_INT);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar producto'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>