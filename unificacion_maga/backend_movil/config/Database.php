<?php
// backend_movil/config/Database.php
// Clase para manejo de conexión a base de datos usando PDO

class Database
{
    // Configuración de la base de datos
    private $host = 'db.vider.maga.aws';
    private $db_name = 'RegistroClimatologico';
    private $username = 'apps_maga';
    private $password = 'f07M0m201t';
    private $charset = 'utf8mb4';
    private $timezone = '-06:00'; // Guatemala GMT-6

    public $conn;

    /**
     * Obtener conexión PDO a la base de datos
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

            // Establecer zona horaria de Guatemala
            $this->conn->exec("SET time_zone = '{$this->timezone}'");

        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos");
        }

        return $this->conn;
    }

    /**
     * Cerrar conexión
     */
    public function closeConnection()
    {
        $this->conn = null;
    }
}
?>