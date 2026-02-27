<?php
/**
 * config/db.php 
 * Configuración de la base de datos
 * Sistema de Registro de Empadronados
 */

// Configuración del servidor
define('DB_HOST', 'localhost');
define('DB_NAME', 'visionwe_RegistroEmpadronados');
define('DB_USER', 'visionwe');
define('DB_PASS', 'Guate25#');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la zona horaria
date_default_timezone_set('America/Guatemala');

// Función para obtener conexión PDO
function obtenerConexion()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        return $pdo;

    } catch (PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        die("Error al conectar con la base de datos. Por favor, contacte al administrador.");
    }
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>