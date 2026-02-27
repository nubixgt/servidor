<?php
/**
 * API para crear producto-precio
 * Endpoint: POST /api/productos/crear.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
if (empty($data->producto) || empty($data->presentacion) || !isset($data->precio) || !isset($data->cantidad)) {
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

    // Verificar si ya existe la combinación producto-presentación
    $checkQuery = "SELECT id FROM productos_precios WHERE producto = :producto AND presentacion = :presentacion";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':producto', $data->producto);
    $checkStmt->bindParam(':presentacion', $data->presentacion);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe un producto con esta presentación'
        ]);
        exit();
    }

    // Insertar producto-precio
    $query = "INSERT INTO productos_precios (producto, presentacion, precio, cantidad) 
              VALUES (:producto, :presentacion, :precio, :cantidad)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':producto', $data->producto);
    $stmt->bindParam(':presentacion', $data->presentacion);
    $stmt->bindParam(':precio', $data->precio);
    $stmt->bindParam(':cantidad', $data->cantidad, PDO::PARAM_INT);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Producto creado exitosamente',
            'data' => [
                'id' => $db->lastInsertId(),
                'producto' => $data->producto,
                'presentacion' => $data->presentacion,
                'precio' => $data->precio,
                'cantidad' => $data->cantidad
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear producto'
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