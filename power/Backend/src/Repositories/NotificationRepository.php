<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Avisos del administrador. Ver Database/011_create_notifications.sql para por
 * que son dos tablas: el aviso es uno y lo ven todos, pero cada atleta puede
 * quitarlo de SU bandeja sin que desaparezca para los demas.
 */
class NotificationRepository
{
    private $pdo;
    private static ?bool $dirigidos = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Si existe `notifications.user_id` (Database/028): un aviso para UNA cuenta (la alerta
     * de gasto al administrador, la respuesta a una consulta de ayuda). NULL = para todos,
     * como siempre. Sin la columna, todo sigue como antes de la 028.
     */
    public function admiteDirigidos(): bool
    {
        if (self::$dirigidos === null) {
            try {
                self::$dirigidos = (bool) $this->pdo->query("SHOW COLUMNS FROM notifications LIKE 'user_id'")->fetch();
            } catch (\Throwable $e) {
                self::$dirigidos = false;
            }
        }
        return self::$dirigidos;
    }

    /**
     * La bandeja de un atleta: todo lo que no ha descartado, lo mas nuevo
     * primero. El LEFT JOIN + IS NULL es lo que descuenta sus descartes sin
     * necesidad de una segunda consulta.
     *
     * @return array<int,array<string,mixed>>
     */
    public function inboxFor(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.id, n.title, n.body, n.created_at, u.name AS author
               FROM notifications n
          LEFT JOIN notification_dismissals d
                 ON d.notification_id = n.id AND d.user_id = :uid
          LEFT JOIN users u ON u.id = n.created_by
              WHERE d.notification_id IS NULL"
            . ($this->admiteDirigidos() ? " AND (n.user_id IS NULL OR n.user_id = :uid2)" : "") . "
           ORDER BY n.created_at DESC, n.id DESC"
        );
        $stmt->execute($this->admiteDirigidos() ? ['uid' => $userId, 'uid2' => $userId] : ['uid' => $userId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /**
     * Todo lo enviado, para el panel del administrador, con cuantos atletas lo
     * han quitado ya de su bandeja.
     *
     * @return array<int,array<string,mixed>>
     */
    public function allSent(): array
    {
        $stmt = $this->pdo->query(
            "SELECT n.id, n.title, n.body, n.created_at, u.name AS author,
                    (SELECT COUNT(*) FROM notification_dismissals d
                      WHERE d.notification_id = n.id) AS dismissed_count
               FROM notifications n
          LEFT JOIN users u ON u.id = n.created_by"
            // Los avisos a una sola cuenta no son "lo enviado a todos".
            . ($this->admiteDirigidos() ? " WHERE n.user_id IS NULL" : "") . "
           ORDER BY n.created_at DESC, n.id DESC"
        );
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /** @return array<string,mixed> el aviso recien creado */
    public function create(int $authorId, string $title, string $body): array
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (title, body, created_by) VALUES (:t, :b, :a)"
        );
        $stmt->execute(['t' => $title, 'b' => $body, 'a' => $authorId]);
        return $this->findById((int) $this->pdo->lastInsertId()) ?? [];
    }

    /**
     * Un aviso para UNA cuenta. Devuelve false si no se pudo (sin la 028) o si ya existía
     * uno con el mismo título para esa cuenta (`$unaVez`): la alerta de gasto se repite
     * cada hora en el cron y tiene que llegar una sola vez por atleta y día.
     */
    public function createFor(int $userId, ?int $authorId, string $title, string $body, bool $unaVez = false,
                              ?int $feedbackId = null): bool
    {
        if (!$this->admiteDirigidos()) {
            return false;
        }
        // La consulta que responde (Database/029); sin la columna se crea igual, sin ella.
        $conConsulta = $feedbackId !== null && $this->tieneColumna('feedback_id');
        if ($unaVez) {
            $q = $this->pdo->prepare('SELECT 1 FROM notifications WHERE user_id = :u AND title = :t LIMIT 1');
            $q->execute(['u' => $userId, 't' => $title]);
            if ($q->fetch()) {
                return false;
            }
        }
        if ($conConsulta) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO notifications (title, body, created_by, user_id, feedback_id) VALUES (:t, :b, :a, :u, :f)'
            );
            $stmt->execute(['t' => $title, 'b' => $body, 'a' => $authorId, 'u' => $userId, 'f' => $feedbackId]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO notifications (title, body, created_by, user_id) VALUES (:t, :b, :a, :u)'
            );
            $stmt->execute(['t' => $title, 'b' => $body, 'a' => $authorId, 'u' => $userId]);
        }
        return true;
    }

    private function tieneColumna(string $columna): bool
    {
        // information_schema y no SHOW COLUMNS: MariaDB no acepta un parámetro en SHOW
        // (error 1064), y tragado aquí dejaba el aviso sin su consulta (prueba local de F3).
        try {
            $q = $this->pdo->prepare(
                'SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'notifications\' AND COLUMN_NAME = :c'
            );
            $q->execute(['c' => $columna]);
            return (bool) $q->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.id, n.title, n.body, n.created_at, u.name AS author
               FROM notifications n
          LEFT JOIN users u ON u.id = n.created_by
              WHERE n.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** Lo retira PARA TODOS. Los descartes se van por CASCADE. */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Lo quita de la bandeja de UN atleta. El aviso sigue vivo para el resto.
     *
     * INSERT IGNORE por la clave primaria compuesta: pulsar la X dos veces, o
     * con mala conexion, no puede ser un error.
     */
    public function dismiss(int $userId, int $id): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO notification_dismissals (notification_id, user_id)
             VALUES (:id, :uid)"
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    /** @param array<string,mixed> $row */
    private function hydrate(array $row): array
    {
        $out = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'createdAt' => $row['created_at'],
            // NULL cuando la cuenta que lo mando ya no existe (ON DELETE SET
            // NULL). El aviso sigue siendo valido; solo se perdio el autor.
            'author' => $row['author'] ?? null,
        ];
        if (isset($row['dismissed_count'])) {
            $out['dismissedCount'] = (int) $row['dismissed_count'];
        }
        return $out;
    }
}
