<?php
namespace App\Utils;

/** Usuario autenticado de la petición actual (lo llena el Router al validar el token). */
class Auth
{
    /** @var array{id:int,username:string,fullName:string,role:string}|null */
    public static ?array $user = null;

    public static function user(): ?array
    {
        return self::$user;
    }
}
