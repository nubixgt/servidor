<?php
namespace App\Utils;

class Security
{
    public static function hashClave(string $clave): string
    {
        return password_hash($clave, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
    }

    public static function verificarClave(string $hash, string $clave): bool
    {
        return password_verify($clave, $hash);
    }

    /** Política: 10+ caracteres, mayúscula, minúscula y número. Devuelve el problema o null. */
    public static function validarClave(string $clave): ?string
    {
        if (mb_strlen($clave) < 10) {
            return 'Debe tener al menos 10 caracteres';
        }
        if (!preg_match('/[A-ZÁÉÍÓÚÑ]/u', $clave) || !preg_match('/[a-záéíóúñ]/u', $clave)) {
            return 'Debe combinar mayúsculas y minúsculas';
        }
        if (!preg_match('/\d/', $clave)) {
            return 'Debe incluir al menos un número';
        }
        return null;
    }

    public static function claveTemporal(): string
    {
        do {
            $clave = self::base64Url(random_bytes(9)) . 'Aa7';
        } while (self::validarClave($clave) !== null);
        return $clave;
    }

    /** Devuelve [token en claro para la cookie, hash para la base]. */
    public static function nuevoRefresh(): array
    {
        $token = self::base64Url(random_bytes(48));
        return [$token, self::hashRefresh($token)];
    }

    public static function hashRefresh(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public static function enmascararDpi(?string $dpi): ?string
    {
        if ($dpi === null || $dpi === '') {
            return $dpi;
        }
        if (strlen($dpi) === 13) {
            return '•••• ••••• ' . substr($dpi, 9);
        }
        return str_repeat('•', max(0, strlen($dpi) - 2)) . substr($dpi, -2);
    }

    /** Texto normalizado para búsqueda: minúsculas, sin tildes, espacios colapsados (igual que ref_f_norm en MySQL). */
    public static function norm(?string $texto): string
    {
        $t = mb_strtolower($texto ?? '', 'UTF-8');
        $t = strtr($t, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n',
        ]);
        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
