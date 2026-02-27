<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida'
    ]);
    exit();
}

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de contrato no proporcionado'
    ]);
    exit();
}

try {
    $conn = getConnection();
    $id = $_GET['id'];

    // Obtener datos del contrato
    $sql = "SELECT * FROM contratos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contrato) {
        echo json_encode([
            'success' => false,
            'message' => 'Contrato no encontrado'
        ]);
        exit();
    }

    // Obtener archivos adjuntos
    $sqlArchivos = "SELECT tipo_archivo, nombre_archivo, ruta_archivo 
                    FROM contrato_archivos 
                    WHERE contrato_id = ?";
    $stmtArchivos = $conn->prepare($sqlArchivos);
    $stmtArchivos->execute([$id]);
    $archivos = $stmtArchivos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'contrato' => $contrato,
        'archivos' => $archivos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el contrato: ' . $e->getMessage()
    ]);
}
?>