<?php
/**
 * API para actualizar el estado de un registro de DPI
 * Recibe: dpi (número de DPI a actualizar)
 * Cambia el estado a 'DPI Físico'
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['dpi']) || empty($data['dpi'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'El número de DPI es requerido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $dpi = $data['dpi'];

    $pdo = getConnection();

    // Verificar que el registro existe
    $sqlCheck = "SELECT fila, nombre, estado FROM planillas_visan_10910 WHERE dpi = :dpi";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute(['dpi' => $dpi]);
    $registro = $stmtCheck->fetch();

    if (!$registro) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el registro con el DPI proporcionado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar si ya está registrado
    if ($registro['estado'] === 'DPI Físico') {
        echo json_encode([
            'success' => true,
            'message' => 'Este DPI ya estaba registrado como DPI Físico',
            'data' => $registro,
            'yaRegistrado' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Actualizar el estado a 'DPI Físico'
    $sqlUpdate = "UPDATE planillas_visan_10910 
                  SET estado = 'DPI Físico' 
                  WHERE dpi = :dpi";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute(['dpi' => $dpi]);

    // Obtener el registro actualizado
    $stmtCheck->execute(['dpi' => $dpi]);
    $registroActualizado = $stmtCheck->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente a DPI Físico',
        'data' => $registroActualizado,
        'yaRegistrado' => false
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el estado',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>