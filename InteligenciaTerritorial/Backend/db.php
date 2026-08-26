<?php
$host = 'localhost';
$dbname = 'inteligencia_territorial'; // Local testing DB name
$username = 'root';
$password = '';

// Si estamos en el servidor cPanel, cambiar las credenciales
if (file_exists('/home/visionwe/config.php')) {
    require_once('/home/visionwe/config.php');
    $dbname = 'visionwe_InteligenciaTerritorial'; 
    $username = 'visionwe_dbuser'; // Asegúrate de que el usuario de cPanel tenga permisos sobre esta DB
    $password = 'Guate25#'; // Cambiar si es necesario
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión a la base de datos']));
}
