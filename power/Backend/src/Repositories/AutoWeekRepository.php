<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * «Generar mi semana automáticamente» (Database/027). Una fila por atleta.
 *
 * **Tolera que la migración no esté**, como todo lo que toca una tabla nueva en
 * este repo: `disponible()` lo comprueba una vez por proceso y, si falta, el
 * interruptor no aparece y el script de las semanas automáticas no hace nada.
 */
class AutoWeekRepository
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
                self::$existe = (bool) $this->pdo->query("SHOW TABLES LIKE 'athlete_auto_weeks'")->fetch();
            } catch (\Throwable $e) {
                error_log('AutoWeekRepository::disponible: ' . $e->getMessage());
                self::$existe = false;
            }
        }
        return self::$existe;
    }

    /** La fila del atleta, o null si nunca tocó el interruptor. */
    public function find(int $userId): ?array
    {
        if (!$this->disponible()) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM athlete_auto_weeks WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /** Los atletas con el interruptor encendido. */
    public function enabled(): array
    {
        if (!$this->disponible()) {
            return [];
        }
        return $this->pdo->query('SELECT * FROM athlete_auto_weeks WHERE enabled = 1 ORDER BY user_id')->fetchAll();
    }

    /** Enciende o apaga. Apagar conserva el resto de la fila: es su historia. */
    public function setEnabled(int $userId, bool $enabled, string $timezone): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO athlete_auto_weeks (user_id, enabled, timezone)
                  VALUES (:u, :e, :tz)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled),
                                     timezone = COALESCE(VALUES(timezone), timezone)'
        );
        $stmt->execute([':u' => $userId, ':e' => $enabled ? 1 : 0, ':tz' => $timezone !== '' ? $timezone : null]);
    }

    /**
     * Lo que pasó con la semana `$weekStart`. `$intento` suma uno a los intentos de
     * esa semana (y los pone a uno si la semana es otra).
     */
    public function setStage(int $userId, string $weekStart, string $stage, string $message,
                             bool $intento = false, ?string $jobId = null): void
    {
        // MySQL asigna de izquierda a derecha y cada SET ve lo ya asignado: `attempts`
        // y `job_id` miran `week_start` ANTES de que cambie.
        $stmt = $this->pdo->prepare(
            'UPDATE athlete_auto_weeks
                SET attempts = CASE WHEN week_start <=> :w THEN attempts + :i ELSE :i2 END,
                    job_id = COALESCE(:j, CASE WHEN week_start <=> :w3 THEN job_id ELSE NULL END),
                    week_start = :w2, stage = :s, message = :m, stage_at = UTC_TIMESTAMP()
              WHERE user_id = :u'
        );
        $stmt->execute([
            ':w' => $weekStart, ':w2' => $weekStart, ':w3' => $weekStart,
            ':i' => $intento ? 1 : 0, ':i2' => $intento ? 1 : 0,
            ':s' => $stage, ':m' => mb_substr($message, 0, 500), ':j' => $jobId, ':u' => $userId,
        ]);
    }
}
