<?php
$host = 'localhost';
$dbname = 'inteligencia_territorial'; // Local testing DB name
$username = 'root';
$password = '';

$httpHost = $_SERVER['HTTP_HOST'] ?? '';

// Si estamos en el servidor cPanel (producción), cambiar las credenciales
if (strpos($httpHost, 'm.nubix.gt') !== false) {
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
