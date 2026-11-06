<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Lo que el atleta cambia de su plan desde la app (Database/031, Beta 3.0 F6-F7).
 *
 * Una fila por sesión tocada, con la versión del coach, la de antes y la de después. Es
 * la fuente de «volver a la del coach» (F7) y de qué cambió el atleta. Nada se borra: volver
 * marca `reverted_at`. Tolera que la migración no esté.
 */
class PlanEditRepository
{
    private $pdo;
    private static ?bool $existe = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function disponible(): bool
    {
        if (self::$existe === null) {
            try {
                self::$existe = (bool) $this->pdo->query("SHOW TABLES LIKE 'athlete_plan_edits'")->fetch();
            } catch (\Throwable $e) {
                self::$existe = false;
            }
            if (!self::$existe) {
                error_log('PlanEditRepository: falta Database/031 (athlete_plan_edits); los cambios no quedan registrados.');
            }
        }
        return self::$existe;
    }

    /**
     * La versión del coach de una sesión: la `before_day` de su PRIMER cambio todavía vivo
     * (sin revertir) que acabó en esa clave. null si nunca se tocó.
     */
    public function original(int $userId, string $clave): ?array
    {
        if (!$this->disponible()) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT original_day FROM athlete_plan_edits
              WHERE user_id = :u AND session_after = :k AND reverted_at IS NULL
           ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['u' => $userId, 'k' => $clave]);
        $fila = $stmt->fetch();
        $dia = $fila ? json_decode((string) $fila['original_day'], true) : null;
        return is_array($dia) ? $dia : null;
    }

    /** Nunca lanza: registrar no puede tumbar un cambio ya guardado. */
    public function registrar(int $userId, string $kind, string $antes, string $despues, ?array $original,
                              ?array $diaAntes, ?array $diaDespues, string $resumen): void
    {
        if (!$this->disponible()) {
            return;
        }
        try {
            $j = fn($v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE);
            $this->pdo->prepare(
                'INSERT INTO athlete_plan_edits (user_id, kind, session_before, session_after, original_day,
                                                 before_day, after_day, summary)
                 VALUES (:u, :k, :a, :d, :o, :b, :f, :s)'
            )->execute([
                'u' => $userId, 'k' => $kind, 'a' => $antes, 'd' => $despues, 'o' => $j($original),
                'b' => $j($diaAntes), 'f' => $j($diaDespues), 's' => mb_substr($resumen, 0, 300),
            ]);
        } catch (\Throwable $e) {
            error_log('PlanEditRepository::registrar: ' . $e->getMessage());
        }
    }

    /**
     * «Volver a la del coach»: los cambios vivos que acabaron en esa sesión quedan revertidos.
     * Se marcan, no se borran (la regla del repo: nada se borra en silencio).
     */
    public function marcarRevertidas(int $userId, string $clave): void
    {
        if (!$this->disponible()) {
            return;
        }
        try {
            $this->pdo->prepare(
                "UPDATE athlete_plan_edits SET reverted_at = NOW()
                  WHERE user_id = :u AND session_after = :k AND reverted_at IS NULL AND kind <> 'revertir'"
            )->execute(['u' => $userId, 'k' => $clave]);
        } catch (\Throwable $e) {
            error_log('PlanEditRepository::marcarRevertidas: ' . $e->getMessage());
        }
    }

    /** Los últimos cambios de un atleta (diagnóstico de ayuda; la semana siguiente). */
    public function ultimos(int $userId, int $limite = 20): array
    {
        if (!$this->disponible()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT kind, session_before, session_after, summary, created_at, reverted_at
               FROM athlete_plan_edits WHERE user_id = :u ORDER BY id DESC LIMIT ' . max(1, min(100, $limite))
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
