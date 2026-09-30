<?php
namespace App\Utils;

class JwtUtils
{
    private static $secret = '54c9a1d67d8b7ed6cd9f08d866950cc5cbd772034841e225e8e065e07f59a4e6';
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
