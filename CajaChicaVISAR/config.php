<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'vales_caja_chica');
define('DB_USER', 'root');
define('DB_PASS', '$Develop3r2025');
define('DB_CHARSET', 'utf8mb4');


// Configuración de zona horaria
date_default_timezone_set('America/Guatemala');


// Configuración de la aplicación
define('APP_NAME', 'Sistema de Vales de Caja Chica');
define('APP_VERSION', '1.0');

// Clase de conexión a la base de datos
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Sincronizar zona horaria de MySQL con Guatemala (UTC-6)
            $this->conn->exec("SET time_zone = '-06:00'");
            
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función auxiliar para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Iniciar sesión
session_start();
?>