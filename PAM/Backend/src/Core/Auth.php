<?php
namespace App\Core;

use App\Entities\Usuario;

/**
 * Usuario autenticado de la petición en curso (lo fija el Router al validar el token).
 */
class Auth
{
    private static ?Usuario $user = null;

    public static function set(?Usuario $user): void
    {
        self::$user = $user;
    }

    public static function user(): ?Usuario
    {
        return self::$user;
    }
}
