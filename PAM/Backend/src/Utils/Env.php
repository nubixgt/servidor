<?php
namespace App\Utils;

class Env
{
    private static ?array $file = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = self::file()[$key] ?? null;
        }
        return ($value === null || $value === '') ? $default : (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null ? $default : in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private static function file(): array
    {
        if (self::$file !== null) {
            return self::$file;
        }
        self::$file = [];
        $path = __DIR__ . '/../../.env';
        if (!is_readable($path)) {
            return self::$file;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            self::$file[trim($name)] = trim(trim($value), "\"'");
        }
        return self::$file;
    }
}
