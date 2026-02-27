<?php
/**
 * Configuración de Base de Datos
 * Sistema de Ejecución Presupuestaria - MAGA
 */

// =====================================================
// CREDENCIALES DE BASE DE DATOS
// =====================================================
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'ejecucion_presupuestaria');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '$Develop3r2025');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Zona horaria
date_default_timezone_set('America/Guatemala');

// Modo debug
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true);

// =====================================================
// CLASE DE CONEXIÓN PDO (Singleton)
// =====================================================
if (!class_exists('Database')) {
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
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    die("Error de conexión: " . $e->getMessage());
                } else {
                    die("Error de conexión a la base de datos");
                }
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
        
        private function __clone() {}
        
        public function __wakeup() {
            throw new Exception("Cannot unserialize singleton");
        }
    }
}

/**
 * Función helper para obtener conexión a BD
 */
if (!function_exists('getDB')) {
    function getDB() {
        return Database::getInstance()->getConnection();
    }
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración de la aplicación
require_once __DIR__ . '/app.php';
