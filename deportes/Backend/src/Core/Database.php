<?php
namespace Core;

use PDO;
use PDOException;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        
        if (strpos($httpHost, 'm.nubix.gt') !== false) {
            // Producción
            $this->host = "localhost";
            $this->db_name = "visionwe_deportes";
            $this->username = "visionwe_deportes";
            $this->password = "DeportesGT2026";
        } else {
            // Local
            $this->host = "localhost";
            $this->db_name = "deportes";
            $this->username = "root";
            $this->password = "";
        }
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
