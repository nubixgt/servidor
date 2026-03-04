<?php
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $stmt = $pdo->query("SHOW COLUMNS FROM congresistas LIKE 'foto'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE congresistas ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER nombre_normalizado");
        echo "Columna 'foto' añadida con éxito.\n";
    } else {
        echo "La columna 'foto' ya existe.\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
