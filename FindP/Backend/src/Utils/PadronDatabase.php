<?php
namespace App\Utils;

use PDO;
use PDOException;

class PadronDatabase
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        // Ruta absoluta hacia padron.db usando el directorio actual
        $dbPath = __DIR__ . '/../../../Diseño/DATA_PADRON/padron.db';
        
        if (!file_exists($dbPath)) {
            die(json_encode(['error' => 'No se encuentra el archivo padron.db en la ruta esperada: ' . $dbPath]));
        }

        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die(json_encode(['error' => 'Error de conexión a la base de datos SQLite del padrón.']));
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
