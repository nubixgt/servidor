<?php
namespace App\Utils;

/**
 * Minimal .env loader (no Composer dependency in this project).
 * Reads KEY=VALUE lines from Backend/.env and exposes them via self::get().
 */
class Env
{
    private static bool $loaded = false;
    private static array $values = [];

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $path = __DIR__ . '/../../.env';
        if (!file_exists($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim($value);
        }
    }

    public static function get(string $key, $default = null)
    {
        self::load();
        return self::$values[$key] ?? $default;
    }

    private static ?array $raiz = null;

    /**
     * Una clave del `.env` de la RAÍZ del repo (el del AiService), sólo leída.
     *
     * Existe para no volver a escribir un secreto como valor por defecto en el
     * código: el token del puente estuvo en `PythonBridgeService` porque en local
     * no hay `Backend/.env`, y así llegó al historial de git. En local, el mismo
     * token ya vive en el `.env` de la raíz como `BRIDGE_SERVICE_TOKEN`. No se
     * mezcla con `get()`: las demás claves de ese fichero no son del backend.
     */
    public static function raiz(string $key, $default = null)
    {
        if (self::$raiz === null) {
            self::$raiz = [];
            $path = __DIR__ . '/../../../.env';
            if (file_exists($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    self::$raiz[trim($k)] = trim(trim($v), "\"'");
                }
            }
        }
        return self::$raiz[$key] ?? $default;
    }
}
