<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\CheckinEntity;

class CheckinRepository
{
    private $pdo;

    private const COLS = [
        'sleep', 'fatigue', 'stress', 'soreness', 'motivation',
        'niggle_location', 'niggle_pain', 'last_week_note', 'week_ahead', 'hooper_total',
    ];

    /**
     * Columnas que sólo existen a partir de una migración concreta.
     *
     * Las migraciones de este repo se corren A MANO, también en producción, así
     * que entre el despliegue del código y la migración hay una ventana en la
     * que la columna no está. Meterla en el INSERT sin comprobar haría fallar
     * el check-in ENTERO durante esa ventana -- y el check-in es lo que abre la
     * generación de la semana, así que el atleta se quedaría sin poder generar.
     * Misma tolerancia que `EventRepository::record()` y `supportsTerms()`.
     */
    private const COLS_OPCIONALES = [
        'week_as_expected',   // migración 016
        'week_note',          // migración 021
        'unavailable_dates',  // migración 022
        'open_dates',         // migración 024
    ];

    private ?array $colsDisponibles = null;

    private function cols(): array
    {
        if ($this->colsDisponibles !== null) {
            return $this->colsDisponibles;
        }
        $extra = [];
        foreach (self::COLS_OPCIONALES as $col) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'athlete_checkins' AND COLUMN_NAME = :c"
                );
                $stmt->execute(['c' => $col]);
                if (((int) $stmt->fetchColumn()) > 0) {
                    $extra[] = $col;
                }
            } catch (\Throwable $e) {
                error_log('CheckinRepository::cols: ' . $e->getMessage());
            }
        }
        $this->colsDisponibles = array_merge(self::COLS, $extra);
        return $this->colsDisponibles;
    }

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** Same-day submissions replace each other (unique key on user_id + checkin_date). */
    public function upsertForDate(int $userId, string $date, array $columns): CheckinEntity
    {
        $cols = $this->cols();
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $updates = array_map(fn($c) => "$c = VALUES($c)", $cols);

        $sql = "INSERT INTO athlete_checkins (user_id, checkin_date, recorded_at, " . implode(', ', $cols) . ")
                VALUES (:user_id, :checkin_date, NOW(), " . implode(', ', $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $updates) . ", recorded_at = NOW()";

        $bind = ['user_id' => $userId, 'checkin_date' => $date];
        foreach ($cols as $c) {
            $bind[$c] = $columns[$c] ?? null;
        }
        $this->pdo->prepare($sql)->execute($bind);

        return $this->findForDate($userId, $date);
    }

    public function findForDate(int $userId, string $date): ?CheckinEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_checkins WHERE user_id = :uid AND checkin_date = :d"
        );
        $stmt->execute(['uid' => $userId, 'd' => $date]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return CheckinEntity[] oldest first */
    public function findAllByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_checkins WHERE user_id = :uid ORDER BY checkin_date ASC"
        );
        $stmt->execute(['uid' => $userId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findLatest(int $userId): ?CheckinEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_checkins WHERE user_id = :uid ORDER BY checkin_date DESC LIMIT 1"
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * The hooper_total of every prior check-in (oldest first), for the personal
     * baseline. Excludes the given date so today's answer isn't compared to itself.
     *
     * @return int[]
     */
    public function hooperHistory(int $userId, string $excludeDate): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT hooper_total FROM athlete_checkins
             WHERE user_id = :uid AND checkin_date <> :d AND hooper_total IS NOT NULL
             ORDER BY checkin_date ASC"
        );
        $stmt->execute(['uid' => $userId, 'd' => $excludeDate]);
        return array_map('intval', array_column($stmt->fetchAll(), 'hooper_total'));
    }

    private function hydrate(array $row): CheckinEntity
    {
        $int = fn($v) => $v !== null ? (int) $v : null;

        return new CheckinEntity(
            id: (int) $row['id'],
            date: (string) $row['checkin_date'],
            sleep: $int($row['sleep']),
            fatigue: $int($row['fatigue']),
            stress: $int($row['stress']),
            soreness: $int($row['soreness']),
            motivation: $int($row['motivation']),
            niggleLocation: (string) $row['niggle_location'],
            nigglePain: $int($row['niggle_pain']),
            lastWeekNote: $row['last_week_note'] !== null ? (string) $row['last_week_note'] : null,
            weekAhead: (string) $row['week_ahead'],
            hooperTotal: $int($row['hooper_total']),
            recordedAt: $row['recorded_at'] !== null ? (string) $row['recorded_at'] : null,
            // `SELECT *` sólo trae la clave si la migración 021 ya se corrió.
            weekNote: isset($row['week_note']) ? (string) $row['week_note'] : null,
            // Migración 022: una lista JSON de fechas, o nada.
            unavailableDates: isset($row['unavailable_dates'])
                ? (json_decode((string) $row['unavailable_dates'], true) ?: [])
                : [],
            weekAsExpected: isset($row['week_as_expected']) ? (string) $row['week_as_expected'] : null,
            // Migración 024: {fecha: [deportes]}, o nada.
            openDates: isset($row['open_dates'])
                ? (json_decode((string) $row['open_dates'], true) ?: [])
                : [],
        );
    }
}
