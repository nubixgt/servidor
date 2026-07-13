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
        // Posibles rutas para la base de datos
        $paths = [
            // Ruta de PRODUCCIÓN: Fuera de la carpeta del proyecto para que el CI/CD no la borre
            '/home/visionwe/datos_padron/padron.db',
            // Ruta de DESARROLLO (Local)
            __DIR__ . '/../../../Diseno/DATA_PADRON/padron.db'
        ];

        $dbPath = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $dbPath = $path;
                break;
            }
        }
        
        if (!$dbPath) {
            die(json_encode(['error' => 'No se encuentra el archivo padron.db en ninguna de las rutas esperadas. Asegúrese de subirlo a /home/visionwe/datos_padron/padron.db']));
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
