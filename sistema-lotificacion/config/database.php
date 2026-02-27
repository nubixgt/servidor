<?php
// Archivo de configuración de la base de datos
// config/database.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'visionwe_Lotificacion');
define('DB_USER', 'visionwe');
define('DB_PASS', 'Guate25#');
define('DB_CHARSET', 'utf8mb4');

// Establecer zona horaria de Guatemala
date_default_timezone_set('America/Guatemala');

// Clase de conexión a la base de datos
class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn = null;

    // Obtener la conexión
    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Establecer zona horaria de MySQL a Guatemala (GMT-6)
            $this->conn->exec("SET time_zone = '-06:00'");

        } catch (PDOException $exception) {
            throw new Exception("Error de conexión: " . $exception->getMessage());
        }

        return $this->conn;
    }
}

// Función para verificar si el usuario está logueado
function verificarSesion()
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Función para cerrar sesión
function cerrarSesion()
{
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Función para actualizar el último acceso
function actualizarUltimoAcceso($usuario_id)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $query = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $usuario_id);
        $stmt->execute();
    } catch (Exception $e) {
        // Error silencioso, no afecta la funcionalidad principal
    }
}

function enviarWassenger($payload)
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.wassenger.com/v1/messages",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Token: fc00991ee322aaadac583134415f3f42cfe71211496758dca0b0fb94dd81abca512b9658282c533a"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($curl);
    curl_close($curl);


}


?>