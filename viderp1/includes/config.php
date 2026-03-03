<?php
/**
 * Configuración de la Base de Datos - VIDER MAGA
 * Sistema de Viceministerio de Desarrollo Económico Rural
 */

// =====================================================
// ZONA HORARIA GUATEMALA - CONFIGURAR PRIMERO
// =====================================================
date_default_timezone_set('America/Guatemala');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'vider_maga');
define('DB_USER', 'root');
define('DB_PASS', '$Develop3r2025');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'VIDER - MAGA');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/vider');

// Configuración de archivos
define('BASE_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('LOG_PATH', BASE_PATH . 'logs/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['xlsx', 'xls', 'csv']);

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . 'error.log');

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
                // Configurar zona horaria Guatemala (UTC-6) en MySQL
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-06:00'"
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
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
    
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function insert($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $this->query($sql, $data);
        return $this->conn->lastInsertId();
    }
    
    public function insertIgnore($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT IGNORE INTO $table ($fields) VALUES ($placeholders)";
        $this->query($sql, $data);
        return $this->conn->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $field) {
            $set[] = "$field = :$field";
        }
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        return $this->query($sql, array_merge($data, $whereParams));
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params);
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
}

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, '.', ',');
}

function formatCurrency($amount) {
    return 'Q ' . number_format($amount, 2, '.', ',');
}

function generateHash($data) {
    return hash('sha256', json_encode($data));
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtener fecha/hora actual de Guatemala
 * Usar esta función en lugar de date() para garantizar hora correcta
 */
function fechaGuatemala($formato = 'Y-m-d H:i:s') {
    return date($formato);
}

/**
 * Obtener timestamp actual de Guatemala
 */
function timestampGuatemala() {
    return date('Y-m-d H:i:s');
}

function logError($message, $context = []) {
    $logEntry = fechaGuatemala() . " - " . $message;
    if (!empty($context)) {
        $logEntry .= " - Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    if (!is_dir(LOG_PATH)) {
        @mkdir(LOG_PATH, 0755, true);
    }
    error_log($logEntry . "\n", 3, LOG_PATH . 'app.log');
}

// Crear directorios si no existen
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}

if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}