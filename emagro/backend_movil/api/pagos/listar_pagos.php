<?php
/**
 * API para listar todos los pagos registrados
 * Endpoint: GET /api/pagos/listar_pagos.php
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

    // Consultar pagos con información de factura y usuario
    $query = "SELECT 
                p.id,
                p.factura_id,
                p.fecha_pago,
                p.banco,
                p.monto_pago,
                p.referencia_transaccion,
                p.fecha_creacion,
                ne.numero_nota,
                ne.cliente_nombre,
                ne.nit,
                ne.total as total_factura,
                u.nombre as usuario_nombre
              FROM pagos p
              INNER JOIN nota_envio ne ON p.factura_id = ne.id
              INNER JOIN usuarios u ON p.usuario_id = u.id
              ORDER BY p.fecha_pago DESC, p.fecha_creacion DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $pagos = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Pagos obtenidos correctamente',
        'data' => $pagos,
        'total' => count($pagos)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>