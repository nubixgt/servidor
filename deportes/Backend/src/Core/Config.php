<?php
namespace Core;

// Lee Backend/config.local.php (o variables de entorno DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, JWT_SECRET).
class Config {
    private static $data = null;

    public static function get($key, $default = null) {
        if (self::$data === null) {
            $archivo = __DIR__ . '/../../config.local.php';
            if (file_exists($archivo)) {
                self::$data = require $archivo;
            } else {
                self::$data = [
                    'db' => [
                        'host' => getenv('DB_HOST') ?: 'localhost',
                        'port' => getenv('DB_PORT') ?: 3306,
                        'name' => getenv('DB_NAME') ?: 'deportes',
                        'user' => getenv('DB_USER') ?: 'root',
                        'pass' => getenv('DB_PASS') ?: '',
                    ],
                    'jwt_secret' => getenv('JWT_SECRET') ?: '',
                    'jwt_ttl' => 604800,
                ];
            }
        }
        $valor = self::$data;
        foreach (explode('.', $key) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) return $default;
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
