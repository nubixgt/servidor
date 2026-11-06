<?php
namespace App\Support;

/**
 * Los costos de IA son SÓLO del administrador (regla del producto), también en la API.
 *
 * Las pantallas los escondían con `store.isAdmin`, pero `/plan`, `/plan/generate-status` y
 * `/plan/history` le mandaban a cualquier atleta `summary.total_cost_usd` y el desglose por
 * modelo (`cost_breakdown`): a un F12 de distancia, que es justo lo que `UsageController` dice
 * que no puede pasar (auditoría del 2026-10-19, como José en local). Esto los quita de la
 * respuesta antes de salir; lo guardado no se toca.
 */
final class SinCostos
{
    /** Claves de dinero o de su desglose, en cualquier nivel de la respuesta. */
    private const CLAVES = ['total_cost_usd', 'cost_usd', 'cost_breakdown', 'cost_summary', 'avg_cost_usd'];

    public static function paraQuien(array $payloadDelToken, mixed $datos): mixed
    {
        return (($payloadDelToken['role'] ?? '') === 'admin') ? $datos : self::quitar($datos);
    }

    public static function quitar(mixed $datos): mixed
    {
        if (!is_array($datos)) {
            return $datos;
        }
        $salida = [];
        foreach ($datos as $clave => $valor) {
            if (is_string($clave) && in_array($clave, self::CLAVES, true)) {
                continue;
            }
            $salida[$clave] = self::quitar($valor);
        }
        return $salida;
    }
}
