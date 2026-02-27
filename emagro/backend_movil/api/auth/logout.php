<?php
/**
 * API de Logout
 * Endpoint: POST /api/auth/logout.php
 * 
 * Este endpoint está preparado para futuras implementaciones
 * como invalidar tokens JWT o registrar el logout
 */

require_once '../../config/cors.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Por ahora solo retornamos éxito
// En el futuro aquí se puede invalidar tokens, registrar logout, etc.
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Logout exitoso'
]);
?>