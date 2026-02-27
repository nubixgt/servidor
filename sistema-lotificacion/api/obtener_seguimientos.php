<?php
// api/obtener_seguimientos.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

header('Content-Type: application/json');

if (!isset($_GET['registro_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de registro no proporcionado']);
    exit();
}

$registro_id = $_GET['registro_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Verificar que el registro pertenece al usuario actual
    $queryVerificar = "SELECT id FROM registros WHERE id = :registro_id AND usuario_id = :usuario_id";
    $stmtVerificar = $conn->prepare($queryVerificar);
    $stmtVerificar->bindParam(':registro_id', $registro_id);
    $stmtVerificar->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmtVerificar->execute();

    if ($stmtVerificar->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este registro']);
        exit();
    }

    // Obtener seguimientos con información del usuario
    $query = "SELECT s.id, s.comentario, s.fecha_creacion, u.nombre_completo as usuario
              FROM seguimiento s
              INNER JOIN usuarios u ON s.usuario_id = u.id
              WHERE s.registro_id = :registro_id
              ORDER BY s.fecha_creacion DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':registro_id', $registro_id);
    $stmt->execute();

    $seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'seguimientos' => $seguimientos]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener seguimientos']);
}
?>