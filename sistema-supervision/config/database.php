<?php
/**
 * Configuración de la Base de Datos
 * Sistema de Supervisión
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'visionwe_SistemaSupervision');
define('DB_USER', 'visionwe'); // Cambia esto por tu usuario de MySQL
define('DB_PASS', 'Guate25#'); // Cambia esto por tu contraseña de MySQL
define('DB_CHARSET', 'utf8mb4');

// Crear conexión PDO
class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // Evitar clonación del objeto
    private function __clone()
    {
    }

    // Evitar deserialización del objeto
    public function __wakeup()
    {
        throw new Exception("No se puede deserializar un singleton.");
    }
}
?>