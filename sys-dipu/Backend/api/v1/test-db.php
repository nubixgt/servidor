<?php
require_once __DIR__ . '/../../autoload.php';

use App\Utils\Database;

header("Content-Type: application/json; charset=UTF-8");

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Connection success
    $response = [
        'status' => 'success',
        'message' => 'Database connection successful!'
    ];

    // 2. Query users (usernames, roles, status)
    $stmt = $db->query("SELECT id, nombre_completo, usuario, rol, estado FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['users'] = $users;

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
