<?php
namespace App\Utils;

/**
 * Mapea cada ronda a su tabla y a las columnas de rubros que le corresponden.
 * Espejo de Frontend/src/utils/rubrics.js -- si cambia un rubro allá, cambia aquí también.
 */
class RondaConfig
{
    const RONDAS = [
        'FASHION_SHOW' => [
            'tabla' => 'calificaciones_fashion_show',
            'rubros' => ['originalidad', 'presentacion', 'coordinacion'],
        ],
        'COREOGRAFIA' => [
            'tabla' => 'calificaciones_coreografia',
            'rubros' => ['coordinacion', 'ritmo', 'desplazamiento'],
        ],
        'GALA' => [
            'tabla' => 'calificaciones_gala',
            'rubros' => ['modelaje', 'seguridad', 'pregunta_o_elegancia'],
        ],
    ];

    public static function get(string $rondaKey): array
    {
        if (!array_key_exists($rondaKey, self::RONDAS)) {
            throw new \Exception("Ronda inválida: '$rondaKey'");
        }
        return self::RONDAS[$rondaKey];
    }
}
