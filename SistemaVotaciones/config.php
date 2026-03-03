<?php
// config.php - Configuración de la base de datos

define('DB_HOST', 'localhost');
define('DB_NAME', 'votaciones-congreso');
define('DB_USER', 'root');  // Cambiar según tu configuración
define('DB_PASS', '$Develop3r2025');      // Cambiar según tu configuración
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Votaciones del Congreso');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

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
        } catch (PDOException $e) {
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

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para normalizar nombres (eliminar acentos y convertir a mayúsculas)
function normalizarNombre($nombre) {
    $nombre = mb_strtoupper($nombre, 'UTF-8');
    $caracteres = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'Ñ' => 'N', 'Ü' => 'U'
    ];
    return strtr($nombre, $caracteres);
}

// Función para formatear fechas
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    if (empty($fecha)) return '';
    $dt = new DateTime($fecha);
    return $dt->format($formato);
}

// Función para sanitizar entrada
function sanitizar($data) {
    if (is_array($data)) {
        return array_map('sanitizar', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Zona horaria
date_default_timezone_set('America/Guatemala');

// Iniciar sesión solo si no está iniciada
// SOLUCIÓN AL ERROR: Verificar si la sesión ya está activa antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}