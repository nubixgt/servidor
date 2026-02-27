<?php
// api/obtener_registro.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit();
}

$id = $_GET['id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener solo si es del usuario actual
    $query = "SELECT id, nombre, apellido, telefono, telefono_americano, como_se_entero, correo, comentario, fecha_registro 
              FROM registros 
              WHERE id = :id AND usuario_id = :usuario_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'registro' => $registro]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener el registro']);
}
?>