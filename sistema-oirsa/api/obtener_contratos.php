<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit();
}

try {
    $conn = getConnection();

    // Obtener todos los contratos
    $sql = "SELECT 
                id,
                numero_contrato,
                servicios,
                fecha_contrato,
                nombre_completo,
                fecha_inicio,
                fecha_fin,
                monto_total
            FROM contratos
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($contratos);

} catch (Exception $e) {
    echo json_encode([]);
}
?>