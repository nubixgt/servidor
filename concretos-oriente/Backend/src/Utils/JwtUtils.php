<?php
namespace App\Utils;

class JwtUtils
{
    private static ?string $secret = null;
    private static string $algo = 'HS256';

    private static function init(): void
    {
        if (self::$secret === null) {
            $configFile = __DIR__ . '/../../config/jwt.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                self::$secret = $config['secret'] ?? 'c7b489d2e1f56a9083b4c6e789a0123456789abcdef0123456789abcdef0123456789abcdef';
                self::$algo = $config['algo'] ?? 'HS256';
            } else {
                self::$secret = getenv('JWT_SECRET') ?: 'c7b489d2e1f56a9083b4c6e789a0123456789abcdef0123456789abcdef0123456789abcdef';
            }
        }
    }

    public static function generate($payload)
    {
        self::init();
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validate($token)
    {
        self::init();
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return false;

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
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
