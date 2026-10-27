<?php
namespace App\Core;

class Request
{
    private static ?string $requestId = null;

    public static function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        if (strcasecmp($name, 'Authorization') === 0) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }
        return '';
    }

    /** IP real del cliente (el proxy envía X-Real-IP / X-Forwarded-For). */
    public static function ip(): ?string
    {
        $candidata = self::header('X-Real-IP')
            ?: trim(explode(',', self::header('X-Forwarded-For'))[0])
            ?: ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($candidata, FILTER_VALIDATE_IP) ?: null;
    }

    public static function userAgent(): ?string
    {
        $ua = self::header('User-Agent');
        return $ua === '' ? null : mb_substr($ua, 0, 255);
    }

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            $id = preg_replace('/[^A-Za-z0-9-]/', '', self::header('X-Request-ID'));
            self::$requestId = $id !== '' ? substr($id, 0, 40) : bin2hex(random_bytes(8));
        }
        return self::$requestId;
    }

    /** Cuerpo JSON de la petición tal cual llega (puede ser un valor suelto, no solo un objeto). */
    public static function jsonCrudo(): mixed
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException('El cuerpo de la petición no es JSON válido', 400);
        }
        return $data;
    }

    /** Cuerpo JSON de la petición como arreglo. */
    public static function json(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
}
