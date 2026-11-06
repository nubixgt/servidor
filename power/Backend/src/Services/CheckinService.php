<?php
namespace App\Services;

use App\Repositories\CheckinRepository;
use App\DTOs\CheckinDTO;

class CheckinService
{
    // Ported from PaginaLocal services/ai/checkin.py
    private const MIN_HISTORY_FOR_BASELINE = 3;
    private const BASELINE_WINDOW = 8;

    private CheckinRepository $repo;

    public function __construct()
    {
        $this->repo = new CheckinRepository();
    }

    /** Every check-in for the user, oldest first (for the AiService bridge). */
    public function historyForUser(int $userId): array
    {
        return array_map(fn($e) => $e->toArray(), $this->repo->findAllByUser($userId));
    }

    public function submitForUser(int $userId, CheckinDTO $dto, string $hoyDelAtleta = ''): array
    {
        $entity = $this->repo->upsertForDate($userId, $this->hoy($hoyDelAtleta), $dto->toColumns());
        return ['saved' => true, 'checkin' => $entity->toArray()];
    }

    /**
     * El día del ATLETA, no el del servidor.
     *
     * El servidor de producción no está en Guatemala, así que a ciertas horas
     * `date('Y-m-d')` devolvía ayer o mañana: el modal —que compara con la
     * fecha local del navegador— volvía a pedir un check-in recién contestado,
     * y la molestia declarada el domingo por la noche llegaba al plan fechada
     * el lunes. Misma solución que `PlanService::reloj()`.
     *
     * Se acepta sólo a un día de distancia: ninguna zona horaria del mundo se
     * separa más de 14 h de UTC, y así un cliente con el reloj estropeado no
     * puede pisar el check-in de otro día ni sembrar filas en el futuro.
     */
    private function hoy(string $delAtleta): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $delAtleta)) {
            return date('Y-m-d');
        }
        $servidor = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $pedido = \DateTimeImmutable::createFromFormat('!Y-m-d', $delAtleta, new \DateTimeZone('UTC'));
        if (!$pedido || abs($pedido->getTimestamp() - $servidor->getTimestamp()) > 86400) {
            return date('Y-m-d');
        }
        return $delAtleta;
    }

    /**
     * The latest check-in + this athlete's own Hooper baseline. Shape mirrors
     * PaginaLocal's GET /api/checkin/latest (minus the LLM prompt `block`).
     */
    public function latestForUser(int $userId): array
    {
        $latest = $this->repo->findLatest($userId);
        if (!$latest) {
            return ['available' => false, 'checkin' => null, 'baseline' => null];
        }

        $history = $this->repo->hooperHistory($userId, $latest->date);

        return [
            'available' => true,
            'checkin' => $latest->toArray(),
            'baseline' => $this->personalBaseline($history),
        ];
    }

    /**
     * This athlete's usual Hooper total, from their recent check-ins. Null
     * until there is enough history for it to mean anything.
     */
    private function personalBaseline(array $totals): ?array
    {
        if (count($totals) < self::MIN_HISTORY_FOR_BASELINE) {
            return null;
        }

        $window = array_slice($totals, -self::BASELINE_WINDOW);
        return [
            'mean' => round(array_sum($window) / count($window), 1),
            'low' => min($window),
            'high' => max($window),
            'samples' => count($window),
        ];
    }
}
