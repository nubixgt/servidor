<?php
/**
 * Endpoint para obtener el siguiente número de nota de envío
 * Método: GET
 */

// Activar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // Obtener el último número de nota
    $sql = "SELECT numero_nota FROM nota_envio ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Extraer el número y sumarle 1
        $ultimoNumero = intval($result['numero_nota']);
        $siguienteNumero = $ultimoNumero + 1;
    } else {
        // Si no hay notas, empezar desde 1
        $siguienteNumero = 1;
    }

    // Formatear con ceros a la izquierda (00001, 00002, etc.)
    $numeroFormateado = str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'numero_nota' => $numeroFormateado
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el siguiente número: ' . $e->getMessage()
    ]);
}
?>