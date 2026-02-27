<?php
/**
 * API para crear un nuevo pago
 * Endpoint: POST /api/pagos/crear_pago.php
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

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar campos requeridos
    if (
        empty($data['factura_id']) || empty($data['fecha_pago']) ||
        empty($data['banco']) || empty($data['monto_pago']) ||
        empty($data['referencia_transaccion']) || empty($data['usuario_id'])
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Todos los campos son requeridos'
        ]);
        exit();
    }

    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar que la factura existe y es de tipo Crédito
    $queryFactura = "SELECT id, numero_nota, total, tipo_venta FROM nota_envio WHERE id = :factura_id";
    $stmtFactura = $db->prepare($queryFactura);
    $stmtFactura->bindParam(':factura_id', $data['factura_id']);
    $stmtFactura->execute();
    $factura = $stmtFactura->fetch();

    if (!$factura) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Factura no encontrada'
        ]);
        exit();
    }

    if ($factura['tipo_venta'] !== 'Crédito') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Solo se pueden registrar pagos para facturas a crédito'
        ]);
        exit();
    }

    // Calcular saldo pendiente
    $querySaldo = "SELECT COALESCE(SUM(monto_pago), 0) as total_pagado 
                   FROM pagos WHERE factura_id = :factura_id";
    $stmtSaldo = $db->prepare($querySaldo);
    $stmtSaldo->bindParam(':factura_id', $data['factura_id']);
    $stmtSaldo->execute();
    $resultSaldo = $stmtSaldo->fetch();
    $totalPagado = $resultSaldo['total_pagado'];
    $saldoPendiente = $factura['total'] - $totalPagado;

    // Validar que el monto no exceda el saldo pendiente
    if ($data['monto_pago'] > $saldoPendiente) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'El monto del pago excede el saldo pendiente de Q' . number_format($saldoPendiente, 2)
        ]);
        exit();
    }

    // Validar que el monto sea mayor a 0
    if ($data['monto_pago'] <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'El monto del pago debe ser mayor a 0'
        ]);
        exit();
    }

    // Insertar el pago
    $query = "INSERT INTO pagos (factura_id, fecha_pago, banco, monto_pago, referencia_transaccion, usuario_id) 
              VALUES (:factura_id, :fecha_pago, :banco, :monto_pago, :referencia_transaccion, :usuario_id)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':factura_id', $data['factura_id']);
    $stmt->bindParam(':fecha_pago', $data['fecha_pago']);
    $stmt->bindParam(':banco', $data['banco']);
    $stmt->bindParam(':monto_pago', $data['monto_pago']);
    $stmt->bindParam(':referencia_transaccion', $data['referencia_transaccion']);
    $stmt->bindParam(':usuario_id', $data['usuario_id']);

    if ($stmt->execute()) {
        $pagoId = $db->lastInsertId();
        $nuevoSaldo = $saldoPendiente - $data['monto_pago'];

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
            'pago_id' => $pagoId,
            'numero_factura' => $factura['numero_nota'],
            'nuevo_saldo' => $nuevoSaldo
        ]);
    } else {
        throw new Exception('Error al insertar el pago');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>