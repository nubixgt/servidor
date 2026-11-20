<?php
namespace App\Core;

use App\Utils\JwtUtils;

/** Lee y valida el token Bearer de la petición actual. */
class Auth
{
    private static ?array $payload = null;

    /** Header Authorization, venga por Apache, CGI/FastCGI o getallheaders(). */
    public static function header(): string
    {
        $value = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($value === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $v) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $value = $v;
                }
            }
        }
        return (string) $value;
    }

    /** Claims del token vigente (sub, usuario, role, nombre). 401 si falta o no sirve. */
    public static function payload(): array
    {
        if (self::$payload !== null) {
            return self::$payload;
        }
        if (!preg_match('/^Bearer\s+(\S+)$/i', self::header(), $m)) {
            throw new HttpException('No autenticado: falta el token', 401);
        }
        $payload = JwtUtils::validate($m[1]);
        if (!$payload || !isset($payload['sub'], $payload['role'])) {
            throw new HttpException('Sesión inválida', 401);
        }
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            throw new HttpException('La sesión expiró, inicia sesión de nuevo', 401);
        }
        return self::$payload = $payload;
    }

    public static function userId(): int
    {
        return (int) self::payload()['sub'];
    }

    public static function role(): string
    {
        return (string) self::payload()['role'];
    }
}
