<?php
/**
 * API para eliminar cliente
 * Endpoint: DELETE /api/clientes/eliminar.php
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
        'message' => 'ID de cliente es requerido'
    ]);
    exit();
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el cliente existe
    $checkQuery = "SELECT id, nombre FROM clientes WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $data->id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Cliente no encontrado'
        ]);
        exit();
    }

    // Eliminar cliente
    $query = "DELETE FROM clientes WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar cliente'
        ]);
    }

} catch (PDOException $e) {
    // Error de restricción de clave foránea (cliente tiene registros asociados)
    if ($e->getCode() == '23000' || strpos($e->getMessage(), '1451') !== false) {
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el cliente porque tiene notas de envío o ventas asociadas. Intente "Bloquear Ventas" en su lugar.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error en la base de datos: ' . $e->getMessage()
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