<?php
/**
 * Endpoint para listar todas las notas de envío con sus productos
 * Método: GET
 */

require_once '../../config/database.php';
require_once '../../config/cors.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    $conn = getConnection();

    // Obtener todas las notas de envío
    $sql = "SELECT 
                ne.*,
                u.nombre as usuario_nombre
            FROM nota_envio ne
            LEFT JOIN usuarios u ON ne.usuario_id = u.id
            ORDER BY ne.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada nota, obtener sus productos
    $sqlDetalles = "SELECT * FROM detalle_nota_envio WHERE nota_envio_id = :nota_id";
    $stmtDetalles = $conn->prepare($sqlDetalles);

    foreach ($notas as &$nota) {
        $stmtDetalles->bindParam(':nota_id', $nota['id']);
        $stmtDetalles->execute();
        $nota['productos'] = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Notas de envío obtenidas correctamente',
        'notas' => $notas,
        'total' => count($notas)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las notas de envío: ' . $e->getMessage()
    ]);
}
?>