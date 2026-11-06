<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * La copia en MySQL del registro de subidas a Garmin (Database/030, Beta 3.0 F5).
 *
 * El registro de verdad sigue siendo `garmin_uploads.json` del AiService: lo único que dice
 * qué entrenos del reloj son nuestros. Esto es su copia, para que entre en los respaldos
 * de la base, y se escribe entera (se reemplaza) cada vez que termina una subida o se
 * borra un plan. **Nunca se usa para borrar nada del reloj** mientras sea copia.
 *
 * Tolera que la migración no esté: sin la tabla no se copia nada.
 */
class GarminUploadRepository
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
                self::$existe = (bool) $this->pdo->query("SHOW TABLES LIKE 'athlete_garmin_uploads'")->fetch();
            } catch (\Throwable $e) {
                self::$existe = false;
            }
        }
        return self::$existe;
    }

    /**
     * Deja la copia de un atleta igual que `$entradas` (las del fichero). En una
     * transacción: una copia a medias sería peor que la de antes. Nunca lanza.
     */
    public function reemplazar(int $userId, array $entradas): bool
    {
        if (!$this->disponible()) {
            return false;
        }
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare('DELETE FROM athlete_garmin_uploads WHERE user_id = :u')->execute(['u' => $userId]);
            $ins = $this->pdo->prepare(
                'INSERT INTO athlete_garmin_uploads (user_id, session_date, session_slot, scheduled_date, workout_id,
                                                     name, sport, fingerprint, kind, adopted, uploaded_at)
                 VALUES (:u, :d, :s, :sd, :w, :n, :sp, :f, :k, :a, :up)'
            );
            $fecha = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
            foreach ($entradas as $e) {
                if (!is_array($e) || empty($e['workout_id'])) {
                    continue;
                }
                $subida = isset($e['uploaded_at']) ? strtotime((string) $e['uploaded_at']) : false;
                $ins->execute([
                    'u' => $userId,
                    'd' => $fecha($e['date'] ?? null),
                    's' => substr((string) ($e['session_slot'] ?? 'unica'), 0, 12) ?: 'unica',
                    'sd' => $fecha($e['scheduled_date'] ?? ($e['date'] ?? null)),
                    'w' => (int) $e['workout_id'],
                    'n' => isset($e['name']) ? mb_substr((string) $e['name'], 0, 191) : null,
                    'sp' => isset($e['sport']) ? substr((string) $e['sport'], 0, 32) : null,
                    'f' => isset($e['fingerprint']) ? substr((string) $e['fingerprint'], 0, 32) : null,
                    'k' => isset($e['kind']) ? substr((string) $e['kind'], 0, 16) : null,
                    'a' => !empty($e['adopted']) ? 1 : 0,
                    'up' => $subida ? date('Y-m-d H:i:s', $subida) : null,
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('GarminUploadRepository::reemplazar: ' . $e->getMessage());
            return false;
        }
    }

    /** La copia de un atleta, con la forma de las entradas del fichero. */
    public function entradas(int $userId): array
    {
        if (!$this->disponible()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT session_date, session_slot, scheduled_date, workout_id, name, sport, fingerprint, kind, adopted
               FROM athlete_garmin_uploads WHERE user_id = :u ORDER BY workout_id'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /** Los atletas con copia, para comparar. */
    public function usuarios(): array
    {
        if (!$this->disponible()) {
            return [];
        }
        return array_map('intval', $this->pdo->query('SELECT DISTINCT user_id FROM athlete_garmin_uploads')
            ->fetchAll(\PDO::FETCH_COLUMN) ?: []);
    }
}
