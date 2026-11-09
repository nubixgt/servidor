<?php
namespace App\Utils;

class JwtUtils
{
    private static $algo = 'HS256';

    /** Lee la configuración de config/jwt.php; si no es válida responde 500. */
    public static function config(): array
    {
        $file = __DIR__ . '/../../config/jwt.php';
        $cfg = is_file($file) ? require $file : [];
        $secret = (string) ($cfg['secret'] ?? '');

        if (strlen($secret) < 32 || str_starts_with($secret, 'CAMBIAR')) {
            error_log('JWT: config/jwt.php falta o tiene la clave de ejemplo');
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'error' => 'Servidor sin configurar (clave de sesión).']);
            exit;
        }

        return ['secret' => $secret, 'ttl' => (int) ($cfg['ttl'] ?? 43200)];
    }

    public static function generate($payload)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::config()['secret'], true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validate($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return false;

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::config()['secret'], true);
        $base64UrlSignature = self::base64UrlEncode($validSignature);

        if (hash_equals($base64UrlSignature, $signature)) {
            return json_decode(self::base64UrlDecode($payload), true);
        }

        return false;
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
