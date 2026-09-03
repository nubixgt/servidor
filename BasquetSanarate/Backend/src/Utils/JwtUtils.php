<?php
namespace App\Utils;

class JwtUtils
{
    private static $secret = 'BasquetSanarate_2025_9f3c1a7e5b204d88ac6e0f21d7b4c9a3';
    private static $algo = 'HS256';

    public static function generate($payload)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validate($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return false;

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($validSignature);

        if (hash_equals($base64UrlSignature, $signature)) {
            return json_decode(self::base64UrlDecode($payload), true);
        }

        return false;
    }

    /**
     * Extrae el token "Bearer" de la petición.
     *
     * En hosting compartido el header Authorization suele no llegar a PHP por
     * getallheaders(); por eso se revisan también las variables de $_SERVER que
     * deja el pass-through de .htaccess (SetEnvIf / CGIPassAuth).
     */
    public static function bearerToken(): ?string
    {
        $header = null;

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }

        if (!$header && function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }

        if (!$header) {
            $header = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';
        }

        if ($header && preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }

        return null;
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
