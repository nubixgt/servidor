<?php
namespace App\Utils;

class JwtUtils
{
    private static $algo = 'HS256';

    private static function secret()
    {
        $secret = Env::get('JWT_SECRET');
        if ($secret) {
            return $secret;
        }
        // Sin JWT_SECRET, una clave escrita en el repositorio firma los tokens
        // y cualquiera que la lea puede fabricarse uno de administrador. Sólo
        // se tolera en local (`php -S` y la línea de comandos); en producción
        // (Apache en cPanel) falla alto antes que aceptarla.
        if (in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
            return 'local-dev-tricoach-jwt-secret';
        }
        throw new \RuntimeException('JWT_SECRET no está configurado en Backend/.env');
    }

    public static function generate($payload)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::secret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validate($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return false;

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::secret(), true);
        $base64UrlSignature = self::base64UrlEncode($validSignature);

        if (hash_equals($base64UrlSignature, $signature)) {
            return json_decode(self::base64UrlDecode($payload), true);
        }

        return false;
    }

    /**
     * Reads and validates the Bearer token from the current request's
     * Authorization header. Returns the decoded payload, or null.
     */
    public static function fromRequest(): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $payload = self::validate($matches[1]);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
