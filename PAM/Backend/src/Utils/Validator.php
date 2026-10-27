<?php
namespace App\Utils;

use App\Core\HttpException;
use DateTimeImmutable;

/**
 * Validaciones de entrada compartidas por los servicios. Lanzan HttpException 400.
 */
class Validator
{
    public static function texto($valor, string $campo, int $min = 0, int $max = 4000, bool $requerido = false): ?string
    {
        $t = $valor === null ? '' : trim((string) $valor);
        if ($t === '') {
            if ($requerido || $min > 0) {
                throw new HttpException("$campo es obligatorio", 400);
            }
            return null;
        }
        $largo = mb_strlen($t);
        if ($largo < $min) {
            throw new HttpException("$campo debe tener al menos $min caracteres", 400);
        }
        if ($largo > $max) {
            throw new HttpException("$campo no puede superar $max caracteres", 400);
        }
        return $t;
    }

    /** Fecha AAAA-MM-DD válida. Devuelve la misma cadena. */
    public static function fecha($valor, string $campo, bool $permitirFutura = true, bool $requerida = true): ?string
    {
        if ($valor === null || $valor === '') {
            if ($requerida) {
                throw new HttpException("$campo es obligatoria", 400);
            }
            return null;
        }
        $d = is_string($valor) ? DateTimeImmutable::createFromFormat('!Y-m-d', $valor) : false;
        if (!$d || $d->format('Y-m-d') !== $valor) {
            throw new HttpException("$campo no es una fecha válida (AAAA-MM-DD)", 400);
        }
        if (!$permitirFutura && $valor > date('Y-m-d')) {
            throw new HttpException('La fecha no puede ser futura', 400);
        }
        return $valor;
    }

    public static function opcion($valor, array $opciones, string $campo): string
    {
        if (!is_string($valor) || !in_array($valor, $opciones, true)) {
            throw new HttpException("$campo no es válido", 400);
        }
        return $valor;
    }

    public static function entero($valor, string $campo, bool $requerido = true): ?int
    {
        if ($valor === null || $valor === '') {
            if ($requerido) {
                throw new HttpException("$campo es obligatorio", 400);
            }
            return null;
        }
        if (filter_var($valor, FILTER_VALIDATE_INT) === false) {
            throw new HttpException("$campo debe ser un número entero", 400);
        }
        return (int) $valor;
    }
}
