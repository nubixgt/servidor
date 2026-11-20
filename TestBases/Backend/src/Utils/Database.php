<?php
namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // In a real API, log this.
            error_log($e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(['status' => 'error', 'message' => 'No se pudo conectar con la base de datos']));
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
        return $this->pdo;
    }
}
