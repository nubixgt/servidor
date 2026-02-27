<?php
// api/agregar_seguimiento.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$registro_id = $_POST['registro_id'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');

// Validaciones
if (empty($registro_id) || empty($comentario)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit();
}

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
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para este registro']);
        exit();
    }

    // Insertar seguimiento
    $query = "INSERT INTO seguimiento (registro_id, comentario, usuario_id) 
              VALUES (:registro_id, :comentario, :usuario_id)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':registro_id', $registro_id);
    $stmt->bindParam(':comentario', $comentario);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Seguimiento agregado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar seguimiento']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el sistema']);
}
?>