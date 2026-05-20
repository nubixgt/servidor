<?php
require_once __DIR__ . '/autoload.php';

use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS calendario_eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT NULL,
        files LONGTEXT NULL COMMENT 'Listado de metadatos de archivos adjuntos en formato JSON',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    
    $db->exec($sql);
    echo "MIGRATION SUCCESS: Table 'calendario_eventos' has been created or already exists.\n";
} catch (\Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
