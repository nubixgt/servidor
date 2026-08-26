<?php
// backend_movil/api/auth/logout.php
// Endpoint para cerrar sesión

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Auth.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido. Use POST', 405);
}

try {
    // Verificar autenticación
    $userId = Auth::requireAuth();

    // En un sistema con base de datos de tokens, aquí se invalidaría el token
    // Por ahora, solo retornamos éxito ya que el cliente eliminará el token

    Response::success(null, 'Sesión cerrada exitosamente');

} catch (Exception $e) {
    error_log("Error en logout: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>