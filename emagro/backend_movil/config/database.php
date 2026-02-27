<?php
/**
 * Configuración de la base de datos
 * 
 * INSTRUCCIONES:
 * 1. Cambia DB_HOST por la dirección de tu servidor MySQL
 * 2. Cambia DB_USER por tu usuario de MySQL
 * 3. Cambia DB_PASS por tu contraseña de MySQL
 * 4. DB_NAME ya está configurado como 'Emagro'
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');        // Cambia esto por tu host
define('DB_USER', 'visionwe');             // Cambia esto por tu usuario
define('DB_PASS', 'Guate25#');                 // Cambia esto por tu contraseña
define('DB_NAME', 'Emagro');           // Nombre de la base de datos
define('DB_CHARSET', 'utf8mb4');

// Zona horaria
date_default_timezone_set('America/Guatemala'); // Ajusta según tu zona horaria

class Database
{
    private $conn;

    /**
     * Obtener conexión a la base de datos
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}

/**
 * Función helper para obtener conexión
 * (Para compatibilidad con código que usa getConnection() directamente)
 */
function getConnection()
{
    $database = new Database();
    return $database->getConnection();
}