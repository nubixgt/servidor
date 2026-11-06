<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\NotebookEntryEntity;

class NotebookRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** @return NotebookEntryEntity[] newest first, all statuses (retired included). */
    public function findAllByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_notebook WHERE user_id = :uid ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute(['uid' => $userId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findByIdForUser(int $userId, int $id): ?NotebookEntryEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM athlete_notebook WHERE user_id = :uid AND id = :id"
        );
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(int $userId, string $text, string $category, string $status): NotebookEntryEntity
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO athlete_notebook (user_id, text, category, source, status)
             VALUES (:uid, :text, :category, 'manual', :status)"
        );
        $stmt->execute([
            'uid' => $userId,
            'text' => $text,
            'category' => $category,
            'status' => $status,
        ]);

        return $this->findByIdForUser($userId, (int) $this->pdo->lastInsertId());
    }

    /** Insert a note the AI coach extracted from a chat turn (born 'vigente'). */
    public function createFromChat(int $userId, string $text, string $category, string $quote): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO athlete_notebook (user_id, text, category, quote, source, status)
             VALUES (:uid, :text, :category, :quote, 'chat', 'vigente')"
        );
        $stmt->execute([
            'uid' => $userId,
            'text' => $text,
            'category' => $category,
            'quote' => $quote !== '' ? $quote : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string,mixed> $fields column => value (already validated)
     */
    public function update(int $userId, int $id, array $fields): ?NotebookEntryEntity
    {
        if (!$fields) {
            return $this->findByIdForUser($userId, $id);
        }

        $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($fields)));
        $stmt = $this->pdo->prepare(
            "UPDATE athlete_notebook SET $set WHERE user_id = :uid AND id = :id"
        );
        $stmt->execute(array_merge($fields, ['uid' => $userId, 'id' => $id]));

        return $this->findByIdForUser($userId, $id);
    }

    /** Si existe la migración 032 (`source = 'reorden'` y `replaced_by`). */
    public function admiteReorden(): bool
    {
        static $si = null;
        if ($si === null) {
            try {
                $q = $this->pdo->query(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'athlete_notebook' AND COLUMN_NAME = 'replaced_by'"
                );
                $si = (int) $q->fetchColumn() > 0;
            } catch (\Throwable $e) {
                $si = false;
            }
        }
        return $si;
    }

    /** Una anotación nueva que sustituye a otras al reordenar (Beta 3.0, F9). Nace vigente. */
    public function createReorden(int $userId, string $text, string $category): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO athlete_notebook (user_id, text, category, source, status)
             VALUES (:uid, :text, :category, :source, 'vigente')"
        );
        $stmt->execute(['uid' => $userId, 'text' => $text, 'category' => $category,
                        'source' => $this->admiteReorden() ? 'reorden' : 'manual']);
        return (int) $this->pdo->lastInsertId();
    }

    /** Retira (nunca borra) con su motivo y, si la hay, la que la sustituye. */
    public function retireFor(int $userId, int $id, string $motivo, ?int $sustituta = null): void
    {
        $campos = "status = 'retirada', retired_at = NOW(), retired_reason = :r";
        $params = ['r' => mb_substr($motivo, 0, 255), 'uid' => $userId, 'id' => $id];
        if ($sustituta !== null && $this->admiteReorden()) {
            $campos .= ', replaced_by = :s';
            $params['s'] = $sustituta;
        }
        $this->pdo->prepare("UPDATE athlete_notebook SET $campos WHERE user_id = :uid AND id = :id AND status IN ('vigente', 'propuesta')")
            ->execute($params);
    }

    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM athlete_notebook WHERE user_id = :uid AND id = :id"
        );
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function hydrate(array $row): NotebookEntryEntity
    {
        return new NotebookEntryEntity(
            id: (int) $row['id'],
            text: (string) $row['text'],
            category: (string) $row['category'],
            quote: $row['quote'] !== null ? (string) $row['quote'] : null,
            source: (string) $row['source'],
            status: (string) $row['status'],
            retiredAt: $row['retired_at'] !== null ? (string) $row['retired_at'] : null,
            retiredReason: (string) $row['retired_reason'],
            createdAt: $row['created_at'] !== null ? (string) $row['created_at'] : null,
            updatedAt: $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
        );
    }
}
