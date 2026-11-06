<?php
namespace App\Services;

use App\Repositories\UsageRepository;

/**
 * Lo que esta cuenta ha costado en APIs.
 *
 * ## Qué es y qué no es
 *
 * Es **costo de proveedor**: lo que la app le pagó a Anthropic, OpenAI y Google
 * por cuenta de este atleta. No es una factura, no lleva impuestos y no sabe de
 * precios de suscripción. Convertirlo en lo que se le cobra es una decisión de
 * negocio que se toma con estos números delante, no aquí dentro.
 *
 * ## De dónde sale
 *
 * La fuente de la verdad es el libro del AiService: sólo-añadir, una línea por
 * corrida con sus tokens, fuera del árbol que se borra. Cada consulta lo lee y
 * vuelca los totales por mes a `athlete_usage`.
 *
 * Si el puente no responde se devuelve el espejo de MySQL. Eso es a propósito:
 * un contador de gasto que desaparece cuando se cae un servicio no sirve para
 * cobrar, y el número de ayer es infinitamente mejor que un guion.
 */
class UsageService
{
    private UsageRepository $repo;

    public function __construct()
    {
        $this->repo = new UsageRepository();
    }

    public function forUser(int $userId): array
    {
        $res = (new PythonBridgeService())->usage($userId);
        $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;

        if (!is_array($datos)) {
            try {
                $espejo = $this->repo->findForUser($userId);
            } catch (\Throwable $e) {
                // Pasa mientras la migración 009 no esté aplicada: sin tabla y
                // sin puente no hay dato que dar, pero un 500 aquí tumbaría el
                // menú entero del atleta por un contador informativo.
                error_log('UsageService: no se pudo leer el espejo: ' . $e->getMessage());
                $espejo = [
                    'total_cost_usd' => 0.0, 'total_tokens' => 0, 'runs' => 0,
                    'by_kind' => [], 'by_month' => [], 'recent' => [],
                ];
            }
            $espejo['stale'] = true;
            $espejo['source'] = 'db';
            return $espejo;
        }

        try {
            $this->repo->syncMonths(
                $userId,
                is_array($datos['by_month'] ?? null) ? $datos['by_month'] : [],
                (int) ($datos['total_tokens'] ?? 0),
                is_array($datos['by_kind'] ?? null) ? $datos['by_kind'] : null
            );
        } catch (\Throwable $e) {
            // Que falle el espejo no puede impedir que el atleta vea su gasto:
            // el dato bueno ya lo tenemos en la mano.
            error_log('UsageService: no se pudo espejar el gasto: ' . $e->getMessage());
        }

        $datos['stale'] = false;
        $datos['source'] = 'ledger';
        return $datos;
    }
}
