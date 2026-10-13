<?php
namespace Utils;

use Core\Config;

class JwtUtils {
    private static function b64($s) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($s));
    }

    private static function secret() {
        $s = Config::get('jwt_secret');
        if (!$s || $s === 'CAMBIAR') throw new \RuntimeException('Falta jwt_secret en Backend/config.local.php');
        return $s;
    }

    public static function generateToken(array $payload) {
        $payload['exp'] = time() + (int)Config::get('jwt_ttl', 604800);
        $h = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $p = self::b64(json_encode($payload));
        $firma = self::b64(hash_hmac('sha256', "$h.$p", self::secret(), true));
        return "$h.$p.$firma";
    }

    // Devuelve el payload si la firma es válida y no ha expirado; si no, false.
    public static function verifyToken($token) {
        $partes = explode('.', $token);
        if (count($partes) !== 3) return false;
        list($h, $p, $firma) = $partes;

        $esperada = self::b64(hash_hmac('sha256', "$h.$p", self::secret(), true));
        if (!hash_equals($esperada, $firma)) return false;

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $p)), true);
        if (!is_array($payload) || empty($payload['exp']) || $payload['exp'] < time()) return false;
        return $payload;
    }

    public static function getBearerToken() {
        $cabecera = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if (!$cabecera && function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $k => $v) {
                if (strtolower($k) === 'authorization') $cabecera = $v;
            }
        }
        return ($cabecera && preg_match('/Bearer\s(\S+)/', $cabecera, $m)) ? $m[1] : null;
    }
}
