<?php
namespace Core;

use PDO;

class Database {
    private static $conn = null;

    // Conexión compartida; los errores SQL lanzan excepciones (las atrapa api/index.php).
    public static function connection() {
        if (self::$conn === null) {
            $dsn = 'mysql:host=' . Config::get('db.host') . ';port=' . Config::get('db.port') . ';dbname=' . Config::get('db.name') . ';charset=utf8mb4';
            self::$conn = new PDO($dsn, Config::get('db.user'), Config::get('db.pass'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$conn;
    }
}
