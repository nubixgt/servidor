<?php
/**
 * API para obtener todos los registros de DPI
 * Retorna JSON compatible con DataTables
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $pdo = getConnection();

    // Consulta para obtener todos los registros
    $sql = "SELECT fila, nombre, dpi, comunidad, estado 
            FROM planillas_visan_10910 
            ORDER BY fila ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $registros = $stmt->fetchAll();

    // Retornar datos en formato compatible con DataTables
    echo json_encode([
        'success' => true,
        'data' => $registros,
        'recordsTotal' => count($registros),
        'recordsFiltered' => count($registros)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los registros',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>