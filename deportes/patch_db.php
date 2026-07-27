<?php
require_once __DIR__ . '/Backend/src/Core/Database.php';

$db = new \Core\Database();
$conn = $db->getConnection();

try {
    $conn->exec("ALTER TABLE equipos ADD COLUMN usuario VARCHAR(50) UNIQUE NULL, ADD COLUMN password_hash VARCHAR(255) NULL");
    echo "SQL Patch executed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
