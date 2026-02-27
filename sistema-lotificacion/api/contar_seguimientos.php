<?php
// api/contar_seguimientos.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener conteo de seguimientos para cada registro del usuario
    $query = "SELECT r.id, COUNT(s.id) as total_seguimientos
              FROM registros r
              LEFT JOIN seguimiento s ON r.id = s.registro_id
              WHERE r.usuario_id = :usuario_id
              GROUP BY r.id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->execute();

    $conteos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode($conteos);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>