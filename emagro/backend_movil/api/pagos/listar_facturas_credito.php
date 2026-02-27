<?php
/**
 * API para listar facturas a crédito con saldo pendiente
 * Endpoint: GET /api/pagos/listar_facturas_credito.php
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

    // Consultar notas de envío a crédito con el total pagado
    $query = "SELECT 
                ne.id,
                ne.numero_nota,
                ne.fecha,
                ne.cliente_id,
                ne.cliente_nombre,
                ne.nit,
                ne.total,
                ne.dias_credito,
                COALESCE(SUM(p.monto_pago), 0) as total_pagado,
                (ne.total - COALESCE(SUM(p.monto_pago), 0)) as saldo_pendiente
              FROM nota_envio ne
              LEFT JOIN pagos p ON ne.id = p.factura_id
              WHERE ne.tipo_venta = 'Crédito'
              GROUP BY ne.id, ne.numero_nota, ne.fecha, ne.cliente_id, 
                       ne.cliente_nombre, ne.nit, ne.total, ne.dias_credito
              HAVING saldo_pendiente > 0
              ORDER BY ne.fecha DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $facturas = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Facturas a crédito obtenidas correctamente',
        'data' => $facturas,
        'total' => count($facturas)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>