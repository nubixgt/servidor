<?php
// api/obtener_registros.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener solo los registros del usuario actual
    $query = "SELECT id, nombre, apellido, telefono, telefono_americano, como_se_entero, correo, comentario, fecha_registro 
              FROM registros 
              WHERE usuario_id = :usuario_id 
              ORDER BY fecha_registro DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($registros);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al obtener registros']);
}
?>