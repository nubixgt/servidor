<?php
namespace App\Utils;

/**
 * Categoría y precio (en quetzales) de las 90 mesas.
 * Debe coincidir con Frontend/src/utils/tables.js. El servidor es quien
 * calcula el monto: nunca se confía en el precio que envía el navegador.
 */
class Tables
{
    public const PRICES = ['platino' => 1600, 'vip' => 1400, 'preferencia' => 1200];

    private const CATEGORY_BY_TABLE = [
        1 => 'platino', 2 => 'platino', 3 => 'platino', 4 => 'platino', 5 => 'platino', 6 => 'vip',
        7 => 'vip', 8 => 'vip', 9 => 'platino', 10 => 'platino', 11 => 'platino', 12 => 'platino',
        13 => 'platino', 14 => 'platino', 15 => 'platino', 16 => 'platino', 17 => 'platino', 18 => 'platino',
        19 => 'vip', 20 => 'vip', 21 => 'vip', 22 => 'vip', 23 => 'vip', 24 => 'vip',
        25 => 'vip', 26 => 'vip', 27 => 'vip', 28 => 'vip', 29 => 'preferencia', 30 => 'preferencia',
        31 => 'preferencia', 32 => 'preferencia', 33 => 'preferencia', 34 => 'preferencia', 35 => 'preferencia', 36 => 'preferencia',
        37 => 'preferencia', 38 => 'preferencia', 39 => 'preferencia', 40 => 'preferencia', 41 => 'preferencia', 42 => 'preferencia',
        43 => 'preferencia', 44 => 'preferencia', 45 => 'preferencia', 46 => 'preferencia', 47 => 'preferencia', 48 => 'preferencia',
        49 => 'preferencia', 50 => 'preferencia', 51 => 'vip', 52 => 'vip', 53 => 'vip', 54 => 'vip',
        55 => 'vip', 56 => 'vip', 57 => 'vip', 58 => 'vip', 59 => 'vip', 60 => 'vip',
        61 => 'vip', 62 => 'vip', 63 => 'platino', 64 => 'platino', 65 => 'platino', 66 => 'platino',
        67 => 'platino', 68 => 'platino', 69 => 'platino', 70 => 'platino', 71 => 'platino', 72 => 'platino',
        73 => 'platino', 74 => 'platino', 75 => 'platino', 76 => 'vip', 77 => 'platino', 78 => 'vip',
        79 => 'preferencia', 80 => 'platino', 81 => 'vip', 82 => 'preferencia', 83 => 'platino', 84 => 'vip',
        85 => 'preferencia', 86 => 'platino', 87 => 'vip', 88 => 'preferencia', 89 => 'vip', 90 => 'preferencia'
    ];

    public static function category(int $tableId): ?string
    {
        return self::CATEGORY_BY_TABLE[$tableId] ?? null;
    }

    public static function price(string $category): int
    {
        return self::PRICES[$category];
    }
}
