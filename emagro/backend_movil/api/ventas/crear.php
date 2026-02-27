<?php
/**
 * API para crear venta
 * Endpoint: POST /api/ventas/crear.php
 * 
 * Body JSON:
 * {
 *   "fecha": "2026-01-20",
 *   "vendedor": "Felipe Machán",
 *   "cliente_id": 1,
 *   "nit": "11652646-7",
 *   "direccion": "Zona 1...",
 *   "tipo_venta": "Contado",
 *   "dias_credito": null,
 *   "producto": "EM1",
 *   "presentacion": "1 litro",
 *   "precio_unitario": 150.00,
 *   "cantidad": 10,
 *   "descuento": 0.00,
 *   "total": 1500.00,
 *   "usuario_id": 1
 * }
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
if (
    empty($data->fecha) || empty($data->vendedor) || empty($data->cliente_id) ||
    empty($data->nit) || empty($data->direccion) || empty($data->tipo_venta) ||
    empty($data->producto) || empty($data->presentacion) || empty($data->precio_unitario) ||
    empty($data->cantidad) || !isset($data->descuento) || empty($data->total) ||
    empty($data->usuario_id)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Todos los campos son requeridos'
    ]);
    exit();
}

// Validar vendedor
$vendedores_validos = ['Felipe Machán', 'Jurandir Terreaux', 'Vinicio Arreaga'];
if (!in_array($data->vendedor, $vendedores_validos)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vendedor inválido'
    ]);
    exit();
}

// Validar tipo de venta
$tipos_venta_validos = ['Contado', 'Crédito', 'Pruebas'];
if (!in_array($data->tipo_venta, $tipos_venta_validos)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de venta inválido'
    ]);
    exit();
}

// Validar dias_credito solo si tipo_venta es Crédito
if ($data->tipo_venta === 'Crédito') {
    if (empty($data->dias_credito) || !is_numeric($data->dias_credito)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Días de crédito es requerido para ventas a crédito'
        ]);
        exit();
    }
} else {
    $data->dias_credito = null;
}

// Validar que cantidad sea numérico
if (!is_numeric($data->cantidad) || $data->cantidad <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'La cantidad debe ser un número mayor a 0'
    ]);
    exit();
}

// Validar que descuento sea numérico
if (!is_numeric($data->descuento) || $data->descuento < 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El descuento debe ser un número mayor o igual a 0'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar que el cliente no esté bloqueado
    $checkQuery = "SELECT bloquear_ventas FROM clientes WHERE id = :cliente_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':cliente_id', $data->cliente_id);
    $checkStmt->execute();

    $cliente = $checkStmt->fetch();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Cliente no encontrado'
        ]);
        exit();
    }

    if ($cliente['bloquear_ventas'] === 'si') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Este cliente tiene las ventas bloqueadas'
        ]);
        exit();
    }

    // Insertar venta
    $query = "INSERT INTO nueva_venta (
                fecha, vendedor, cliente_id, nit, direccion, tipo_venta, dias_credito,
                producto, presentacion, precio_unitario, cantidad, descuento, total, usuario_id
              ) VALUES (
                :fecha, :vendedor, :cliente_id, :nit, :direccion, :tipo_venta, :dias_credito,
                :producto, :presentacion, :precio_unitario, :cantidad, :descuento, :total, :usuario_id
              )";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':fecha', $data->fecha);
    $stmt->bindParam(':vendedor', $data->vendedor);
    $stmt->bindParam(':cliente_id', $data->cliente_id);
    $stmt->bindParam(':nit', $data->nit);
    $stmt->bindParam(':direccion', $data->direccion);
    $stmt->bindParam(':tipo_venta', $data->tipo_venta);
    $stmt->bindParam(':dias_credito', $data->dias_credito);
    $stmt->bindParam(':producto', $data->producto);
    $stmt->bindParam(':presentacion', $data->presentacion);
    $stmt->bindParam(':precio_unitario', $data->precio_unitario);
    $stmt->bindParam(':cantidad', $data->cantidad);
    $stmt->bindParam(':descuento', $data->descuento);
    $stmt->bindParam(':total', $data->total);
    $stmt->bindParam(':usuario_id', $data->usuario_id);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Venta registrada exitosamente',
            'data' => [
                'id' => $db->lastInsertId(),
                'fecha' => $data->fecha,
                'vendedor' => $data->vendedor,
                'producto' => $data->producto,
                'total' => $data->total
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al registrar venta'
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