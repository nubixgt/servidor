<?php
/**
 * API para listar usuarios
 * Endpoint: GET /api/usuarios/listar.php
 * 
 * Retorna todos los usuarios (sin contraseñas)
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

    // Consultar usuarios
    $query = "SELECT id, nombre, usuario, rol, estado, fecha_creacion, fecha_actualizacion 
              FROM usuarios 
              ORDER BY id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $usuarios = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Usuarios obtenidos correctamente',
        'data' => $usuarios,
        'total' => count($usuarios)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>