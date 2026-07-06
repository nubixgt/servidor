<?php
namespace App\Utils;

class JwtUtils
{
    private static $algo = 'HS256';

    private static function getSecret()
    {
        $configFile = __DIR__ . '/../../config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            return $config['jwt_secret'] ?? 'YOUR_SECRET_KEY_CHANGE_ME';
        }
        return 'YOUR_SECRET_KEY_CHANGE_ME';
    }

    public static function generate($payload)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);
        $secret = self::getSecret();

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validate($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return false;

        [$header, $payload, $signature] = $parts;
        $secret = self::getSecret();

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($validSignature);

        if ($base64UrlSignature === $signature) {
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
