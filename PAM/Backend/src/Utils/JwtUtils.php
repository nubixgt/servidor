<?php
namespace App\Utils;

use RuntimeException;

class JwtUtils
{
    private static $algo = 'HS256';

    public static function generate($payload)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::secret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /** Devuelve el payload si la firma es válida; false en cualquier otro caso. */
    public static function validate($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return false;

        [$header, $payload, $signature] = $parts;

        $headerData = json_decode(self::base64UrlDecode($header), true);
        if (($headerData['alg'] ?? null) !== self::$algo)
            return false;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::secret(), true);
        $base64UrlSignature = self::base64UrlEncode($validSignature);

        if (hash_equals($base64UrlSignature, $signature)) {
            return json_decode(self::base64UrlDecode($payload), true);
        }

        return false;
    }

    private static function secret(): string
    {
        $secret = (require __DIR__ . '/../../config/app.php')['jwt_secret'];
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET no está configurado en Backend/.env (mínimo 32 caracteres)');
        }
        return $secret;
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
