<?php
namespace App\Utils;

/** Utilidades compartidas por Cultivos, Pecuario e Infraestructura: período de monitoreo, semana y familias. */
class Periodo
{
    /** Valida que sea una fecha Y-m-d real; '' / null se aceptan como "sin valor". */
    public static function fecha(?string $v, string $etiqueta): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            throw new \Exception("$etiqueta no es una fecha válida.", 400);
        }
        return $v;
    }

    /** Valida el rango de monitoreo (ambos opcionales, pero si hay uno debe haber el otro). */
    public static function rango(?string $inicio, ?string $fin): void
    {
        self::fecha($inicio, 'Monitoreo inicio');
        self::fecha($fin, 'Monitoreo fin');
        if (($inicio === null || $inicio === '') !== ($fin === null || $fin === '')) {
            throw new \Exception('Indica el inicio y el fin del período de monitoreo.', 400);
        }
        if ($inicio && $fin && $fin < $inicio) {
            throw new \Exception('El fin del monitoreo no puede ser anterior al inicio.', 400);
        }
    }

    /** @return array{0:int,1:int} [semana ISO, año ISO] */
    public static function semana(string $fecha): array
    {
        $d = new \DateTime($fecha);
        return [(int)$d->format('W'), (int)$d->format('o')];
    }

    /** Un conteo no puede ser negativo. */
    public static function noNegativo($v, string $etiqueta): void
    {
        if ($v !== null && $v < 0) {
            throw new \Exception("$etiqueta no puede ser negativo.", 400);
        }
    }

    /** Convierte un número del DTO a entero (o null). */
    public static function entero($v): ?int
    {
        return $v === null ? null : (int)round($v);
    }
}
