<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class BudgetExtensionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByProject(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM budget_extensions WHERE project_id = :project_id ORDER BY created_at DESC"
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO budget_extensions (project_id, monto, tipo_ampliacion, created_by)
             VALUES (:project_id, :monto, :tipo_ampliacion, :created_by)"
        );
        $stmt->execute([
            'project_id'      => $data['project_id'],
            'monto'           => $data['monto'],
            'tipo_ampliacion' => $data['tipo_ampliacion'],
            'created_by'      => $data['created_by'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateDocuments(int $id, string $json): void
    {
        $stmt = $this->pdo->prepare("UPDATE budget_extensions SET documentos = :documentos WHERE id = :id");
        $stmt->execute(['documentos' => $json, 'id' => $id]);
    }
}
