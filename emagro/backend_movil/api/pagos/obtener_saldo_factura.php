<?php
/**
 * API para obtener el saldo pendiente de una factura específica
 * Endpoint: GET /api/pagos/obtener_saldo_factura.php?factura_id=X
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
    // Validar parámetro factura_id
    if (empty($_GET['factura_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'El parámetro factura_id es requerido'
        ]);
        exit();
    }

    $facturaId = $_GET['factura_id'];

    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener información de la factura
    $queryFactura = "SELECT id, numero_nota, total, tipo_venta FROM nota_envio WHERE id = :factura_id";
    $stmtFactura = $db->prepare($queryFactura);
    $stmtFactura->bindParam(':factura_id', $facturaId);
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

    // Calcular total pagado
    $querySaldo = "SELECT COALESCE(SUM(monto_pago), 0) as total_pagado 
                   FROM pagos WHERE factura_id = :factura_id";
    $stmtSaldo = $db->prepare($querySaldo);
    $stmtSaldo->bindParam(':factura_id', $facturaId);
    $stmtSaldo->execute();
    $resultSaldo = $stmtSaldo->fetch();
    $totalPagado = $resultSaldo['total_pagado'];
    $saldoPendiente = $factura['total'] - $totalPagado;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Saldo obtenido correctamente',
        'data' => [
            'factura_id' => $factura['id'],
            'numero_nota' => $factura['numero_nota'],
            'total_factura' => $factura['total'],
            'total_pagado' => $totalPagado,
            'saldo_pendiente' => $saldoPendiente,
            'tipo_venta' => $factura['tipo_venta']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>