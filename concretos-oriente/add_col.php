<?php
$config = require __DIR__ . '/Backend/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    $pdo->exec("ALTER TABLE expenses ADD COLUMN dependiente VARCHAR(150) NULL");
    echo "Added dependiente to expenses\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
