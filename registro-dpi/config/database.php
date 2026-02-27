<?php
/**
 * Configuración de conexión a la base de datos
 * Base de datos: DPI
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'DPI');
define('DB_USER', 'visionwe_dpi'); // Cambiar según tus credenciales
define('DB_PASS', 'Guate25#');     // Cambiar según tus credenciales
define('DB_PORT', '3306'); // Puerto por defecto de MySQL
define('DB_CHARSET', 'utf8mb4');

/**
 * Función para obtener la conexión a la base de datos
 * @return PDO Objeto de conexión PDO
 * @throws PDOException Si hay error en la conexión
 */
function getConnection()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        return $pdo;

    } catch (PDOException $e) {
        // Log del error (en producción, usar un sistema de logs apropiado)
        error_log("Error de conexión a la base de datos: " . $e->getMessage());

        // Retornar error genérico al cliente
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos'
        ]);
        exit;
    }
}
?>