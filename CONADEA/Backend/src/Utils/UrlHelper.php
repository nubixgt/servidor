<?php
namespace App\Utils;

class UrlHelper
{
    /**
     * URL pública base del backend (esquema + host + ruta), calculada igual
     * que Router::dispatch() resuelve la ruta base: el entry point siempre
     * vive en <base>/api/v1/index.php, así que subir 3 niveles desde
     * SCRIPT_NAME da <base>, sin importar qué tan anidado esté el deploy.
     */
    public static function base(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $base = dirname(dirname(dirname($scriptName)));
        $base = ($base === '/' || $base === '\\') ? '' : $base;

        return "$scheme://$host$base";
    }

    public static function toAbsolute(string $relativePath): string
    {
        return self::base() . '/' . ltrim($relativePath, '/');
    }
}
