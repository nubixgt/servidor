<?php
namespace App\Core;

/**
 * Guarda el payload del JWT ya validado por el Router para que los controladores
 * sepan qué usuario (jurado) está haciendo la petición actual.
 */
class Auth
{
    private static ?array $payload = null;

    public static function setPayload(array $payload): void
    {
        self::$payload = $payload;
    }

    public static function user(): ?array
    {
        return self::$payload;
    }

    public static function id(): ?int
    {
        return isset(self::$payload['sub']) ? (int) self::$payload['sub'] : null;
    }
}
