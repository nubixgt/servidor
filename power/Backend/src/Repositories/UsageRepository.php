<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * El espejo del libro de costos, agregado por mes.
 *
 * La fuente de la verdad vive en el AiService (`core/usage_ledger.py`): un
 * `.jsonl` de sólo-añadir, con una línea por corrida y sus tokens, fuera del
 * árbol que se borra. Esto es una copia agregada para poder consultarla y
 * facturarla desde el backend aunque el servicio de Python esté caído.
 *
 * Si las dos cifras discrepan, **manda el `.jsonl`** — y que discrepen es señal
 * de que la sincronización se rompió, no de que haya que elegir la mayor.
 *
 * `athlete_usage` no tiene clave foránea a `users` a propósito (ver la
 * migración 009): borrar al usuario no puede borrar lo que ya se gastó, porque
 * la factura del proveedor llega igual.
 */
class UsageRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Vuelca los totales por mes que devolvió el puente.
     *
     * Se reemplaza el mes entero en vez de sumar: el puente manda el total
     * acumulado del mes, no el incremento. Sumarlo duplicaría el gasto en cada
     * consulta, que es la forma más rápida de facturar de más.
     *
     * @param array<string,array{cost_usd?:float,runs?:int}> $byMonth
     */
    public function syncMonths(int $userId, array $byMonth, int $tokens, ?array $byKind): void
    {
        if (!$byMonth) {
            return;
        }
        $sql = "INSERT INTO athlete_usage (user_id, month, cost_usd, runs, tokens, by_kind)
                VALUES (:user_id, :month, :cost_usd, :runs, :tokens, :by_kind)
                ON DUPLICATE KEY UPDATE
                    cost_usd = VALUES(cost_usd),
                    runs     = VALUES(runs),
                    tokens   = VALUES(tokens),
                    by_kind  = VALUES(by_kind)";
        $stmt = $this->pdo->prepare($sql);

        // Los tokens y el desglose que manda el puente son del TOTAL histórico,
        // no de un mes. Se guardan sólo en la fila del mes más reciente para no
        // repetir la misma cifra en cada mes y que la suma mienta.
        $ultimo = max(array_keys($byMonth));
        foreach ($byMonth as $month => $datos) {
            $esUltimo = ((string) $month === (string) $ultimo);
            $stmt->execute([
                'user_id'  => $userId,
                'month'    => substr((string) $month, 0, 7),
                'cost_usd' => round((float) ($datos['cost_usd'] ?? 0), 6),
                'runs'     => (int) ($datos['runs'] ?? 0),
                'tokens'   => $esUltimo ? $tokens : 0,
                'by_kind'  => $esUltimo && $byKind ? json_encode($byKind, JSON_UNESCAPED_UNICODE) : null,
            ]);
        }
    }

    /** Lo guardado para este usuario. Es lo que se enseña si el puente no responde. */
    public function findForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT month, cost_usd, runs, tokens, by_kind
               FROM athlete_usage
              WHERE user_id = :uid
           ORDER BY month DESC"
        );
        $stmt->execute(['uid' => $userId]);
        $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $byMonth = [];
        $total = 0.0;
        $runs = 0;
        $tokens = 0;
        $byKind = [];
        foreach ($filas as $f) {
            $byMonth[$f['month']] = [
                'cost_usd' => (float) $f['cost_usd'],
                'runs'     => (int) $f['runs'],
            ];
            $total += (float) $f['cost_usd'];
            $runs  += (int) $f['runs'];
            $tokens += (int) $f['tokens'];
            if (!$byKind && !empty($f['by_kind'])) {
                $byKind = json_decode((string) $f['by_kind'], true) ?: [];
            }
        }

        return [
            'total_cost_usd' => round($total, 6),
            'total_tokens'   => $tokens,
            'runs'           => $runs,
            'by_kind'        => $byKind,
            'by_month'       => $byMonth,
            'recent'         => [],
        ];
    }
}
