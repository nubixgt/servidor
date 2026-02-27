<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ultimo_numero' => null]);
    exit();
}

try {
    $conn = getConnection();

    // Obtener el año actual
    $año_actual = date('Y');

    // Buscar el último contrato del año actual
    $sql = "SELECT numero_contrato 
            FROM contratos 
            WHERE numero_contrato LIKE ?
            ORDER BY id DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $patron = "%-{$año_actual}-%";
    $stmt->execute([$patron]);

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ultimo_numero' => $resultado ? $resultado['numero_contrato'] : null
    ]);

} catch (Exception $e) {
    echo json_encode(['ultimo_numero' => null]);
}
?>