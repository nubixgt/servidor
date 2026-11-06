<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Lo que el atleta levantó de verdad (Database/026).
 *
 * Una fila por sesión de gimnasio (día + franja) con sus ejercicios dentro. El
 * `exercise_id` es el del programa confirmado, que es lo que permite seguir el
 * mismo ejercicio semana a semana para progresar la carga.
 *
 * **Tolera que la migración no esté**, como todo lo que toca una tabla nueva en
 * este repo: `disponible()` lo comprueba una vez por petición y, si falta, la app
 * se comporta como antes -- no se registra nada y el atleta no ve ningún error.
 */
class StrengthLogRepository
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
                self::$existe = (bool) $this->pdo->query("SHOW TABLES LIKE 'athlete_strength_logs'")->fetch();
            } catch (\Throwable $e) {
                error_log('StrengthLogRepository::disponible: ' . $e->getMessage());
                self::$existe = false;
            }
        }
        return self::$existe;
    }

    /** Lo registrado entre dos fechas, lo más reciente primero. */
    public function between(int $userId, string $desde, string $hasta): array
    {
        if (!$this->disponible()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT fecha, session_slot, routine_session_id, exercises, note, updated_at
               FROM athlete_strength_logs
              WHERE user_id = :u AND fecha BETWEEN :d AND :h
              ORDER BY fecha DESC, session_slot ASC'
        );
        $stmt->execute([':u' => $userId, ':d' => $desde, ':h' => $hasta]);
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    /** Las últimas sesiones registradas, para la progresión. */
    public function latest(int $userId, int $limite = 12): array
    {
        if (!$this->disponible()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT fecha, session_slot, routine_session_id, exercises, note, updated_at
               FROM athlete_strength_logs
              WHERE user_id = :u
              ORDER BY fecha DESC, session_slot ASC
              LIMIT ' . max(1, min(60, $limite))
        );
        $stmt->execute([':u' => $userId]);
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    /**
     * Guarda (o reemplaza) lo hecho en una sesión. Devuelve false si la migración
     * no está: quien llama se lo dice al atleta en vez de fingir que se guardó.
     */
    public function save(int $userId, string $fecha, string $slot, ?string $sesionDelPrograma,
                         array $ejercicios, string $nota): bool
    {
        if (!$this->disponible()) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO athlete_strength_logs (user_id, fecha, session_slot, routine_session_id, exercises, note)
                  VALUES (:u, :d, :s, :r, :e, :n)
             ON DUPLICATE KEY UPDATE routine_session_id = VALUES(routine_session_id),
                                     exercises = VALUES(exercises), note = VALUES(note)'
        );
        $stmt->execute([
            ':u' => $userId,
            ':d' => $fecha,
            ':s' => $slot,
            ':r' => $sesionDelPrograma !== '' ? $sesionDelPrograma : null,
            ':e' => json_encode($ejercicios, JSON_UNESCAPED_UNICODE),
            ':n' => $nota,
        ]);
        return true;
    }

    /** Borra lo registrado de una sesión (el atleta puede deshacerlo). */
    public function delete(int $userId, string $fecha, string $slot): bool
    {
        if (!$this->disponible()) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'DELETE FROM athlete_strength_logs WHERE user_id = :u AND fecha = :d AND session_slot = :s'
        );
        $stmt->execute([':u' => $userId, ':d' => $fecha, ':s' => $slot]);
        return true;
    }

    private function hidratar(array $fila): array
    {
        $ejercicios = json_decode((string) ($fila['exercises'] ?? '[]'), true);
        return [
            'fecha' => (string) $fila['fecha'],
            'session_slot' => (string) $fila['session_slot'],
            'routine_session_id' => $fila['routine_session_id'] ?: null,
            'exercises' => is_array($ejercicios) ? $ejercicios : [],
            'note' => (string) ($fila['note'] ?? ''),
            'updated_at' => (string) ($fila['updated_at'] ?? ''),
        ];
    }
}
