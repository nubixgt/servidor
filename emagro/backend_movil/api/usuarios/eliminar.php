<?php
/**
 * API para eliminar usuario
 * Endpoint: DELETE /api/usuarios/eliminar.php
 * 
 * Body JSON:
 * {
 *   "id": 1
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

// Solo permitir DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener datos JSON
$data = json_decode(file_get_contents("php://input"));

// Validar ID
if (empty($data->id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario es requerido'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el usuario existe
    $checkQuery = "SELECT id, usuario FROM usuarios WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data->id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit();
    }

    // Eliminar usuario
    $query = "DELETE FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar usuario'
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