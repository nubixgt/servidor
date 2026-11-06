<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Una fila por generación lanzada (Database/028), para el tope de versiones (D2, Beta 3.0 F2).
 *
 * Decisión del responsable (2026-10-10): como mucho 3 versiones de una misma semana y 1
 * macrociclo cada 7 días; los fallos no gastan cupo; la semana automática no cuenta.
 *
 * **Qué semana se va a pagar lo decide el cerebro** (`server.semana_objetivo`), no esto:
 * aquí se guardan las semanas generadas y se mandan al puente, que dice si queda cupo
 * (`services/ai/cupo_de_generaciones.py`). Las dos constantes valen lo mismo en los dos
 * lados; una prueba de PaginaLocal lo fija.
 *
 * **Tolera que la migración no esté**: sin la tabla no hay tope (se anota en el error_log
 * una vez por proceso) y nada se rompe.
 */
class GenerationRepository
{
    public const TOPE_POR_SEMANA = 3;
    public const DIAS_ENTRE_MACROCICLOS = 7;

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
                self::$existe = (bool) $this->pdo->query("SHOW TABLES LIKE 'athlete_generations'")->fetch();
            } catch (\Throwable $e) {
                self::$existe = false;
            }
            if (!self::$existe) {
                error_log('GenerationRepository: falta Database/028 (athlete_generations); no hay tope de generaciones.');
            }
        }
        return self::$existe;
    }

    /** Se lanzó una generación. Nunca lanza: medir no puede tumbar una generación pagada. */
    public function registrar(int $userId, string $kind, ?string $targetWeek, ?string $jobId, bool $automatica): void
    {
        if (!$this->disponible()) {
            return;
        }
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO athlete_generations (user_id, kind, target_week, job_id, automatic)
                 VALUES (:u, :k, :w, :j, :a)'
            );
            $stmt->execute([
                ':u' => $userId, ':k' => $kind,
                ':w' => $this->fecha($targetWeek), ':j' => $jobId ?: null, ':a' => $automatica ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            error_log('GenerationRepository::registrar: ' . $e->getMessage());
        }
    }

    /**
     * Terminó. `$targetWeek` corrige la semana con la que de verdad se escribió (la
     * primera fecha de la semana nueva). Sólo cierra una fila abierta: volver a preguntar
     * el estado no la reescribe.
     */
    public function cerrar(int $userId, string $jobId, bool $ok, ?string $error = null, ?string $targetWeek = null): void
    {
        if (!$this->disponible() || $jobId === '') {
            return;
        }
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE athlete_generations
                    SET status = :s, error = :e, finished_at = CURRENT_TIMESTAMP,
                        target_week = COALESCE(:w, target_week)
                  WHERE user_id = :u AND job_id = :j AND status = 'started'"
            );
            $stmt->execute([
                ':s' => $ok ? 'ok' : 'error',
                ':e' => $ok ? null : mb_substr((string) $error, 0, 500),
                ':w' => $this->fecha($targetWeek),
                ':u' => $userId, ':j' => $jobId,
            ]);
        } catch (\Throwable $e) {
            error_log('GenerationRepository::cerrar: ' . $e->getMessage());
        }
    }

    /**
     * Los inicios de las semanas que cuentan para el tope: no automáticas y sin error, de
     * los últimos 60 días (una semana más vieja ya no se puede volver a generar).
     * null = sin tabla, y entonces el puente no aplica tope.
     *
     * @return list<string>|null
     */
    public function semanasQueCuentan(int $userId): ?array
    {
        if (!$this->disponible()) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT target_week FROM athlete_generations
              WHERE user_id = :u AND kind = 'week' AND automatic = 0 AND status <> 'error'
                AND target_week IS NOT NULL AND created_at >= (CURRENT_TIMESTAMP - INTERVAL 60 DAY)"
        );
        $stmt->execute([':u' => $userId]);
        return array_map(fn($f) => (string) $f['target_week'], $stmt->fetchAll());
    }

    /**
     * Desde cuándo puede generar otro macrociclo (fecha y hora de la base), o null si ya puede.
     * Cuenta el último que no falló.
     */
    public function macrocicloDisponibleDesde(int $userId): ?string
    {
        if (!$this->disponible()) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT created_at + INTERVAL " . self::DIAS_ENTRE_MACROCICLOS . " DAY AS desde
               FROM athlete_generations
              WHERE user_id = :u AND kind = 'season' AND status <> 'error'
                AND created_at > (CURRENT_TIMESTAMP - INTERVAL " . self::DIAS_ENTRE_MACROCICLOS . " DAY)
           ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':u' => $userId]);
        $fila = $stmt->fetch();
        return $fila ? (string) $fila['desde'] : null;
    }

    /** Las últimas generaciones de un atleta (para el diagnóstico de «Pedir ayuda»). */
    public function ultimas(int $userId, int $limite = 5): array
    {
        if (!$this->disponible()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT kind, target_week, job_id, automatic, status, error, created_at, finished_at
               FROM athlete_generations WHERE user_id = :u ORDER BY id DESC LIMIT ' . max(1, min(20, $limite))
        );
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll();
    }

    private function fecha(?string $valor): ?string
    {
        return $valor !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : null;
    }
}
