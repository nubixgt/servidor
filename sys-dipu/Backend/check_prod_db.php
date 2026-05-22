<?php
header('Content-Type: application/json');
require_once __DIR__ . '/autoload.php';
use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'tables' => $tables,
        'details' => []
    ];
    
    // Check fiscalizacion_documentos table
    if (in_array('fiscalizacion_documentos', $tables)) {
        $stmt = $db->query("DESCRIBE fiscalizacion_documentos");
        $result['details']['fiscalizacion_documentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result['details']['fiscalizacion_documentos'] = 'MISSING';
    }
    
    // Check fiscalizacion_alertas table
    if (in_array('fiscalizacion_alertas', $tables)) {
        $stmt = $db->query("DESCRIBE fiscalizacion_alertas");
        $result['details']['fiscalizacion_alertas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result['details']['fiscalizacion_alertas'] = 'MISSING';
    }

    // Check fiscalizacion_ministros table
    if (in_array('fiscalizacion_ministros', $tables)) {
        $stmt = $db->query("DESCRIBE fiscalizacion_ministros");
        $result['details']['fiscalizacion_ministros'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result['details']['fiscalizacion_ministros'] = 'MISSING';
    }

    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
