<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class BudgetRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findBudgetItems(?int $projectId): array
    {
        $sql = "SELECT bi.*, p.nombre as project_name 
                FROM budget_items bi 
                LEFT JOIN projects p ON bi.project_id = p.id ";
        
        if ($projectId) {
            $sql .= " WHERE bi.project_id = :project_id ";
        }
        $sql .= " ORDER BY bi.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        if ($projectId) {
            $stmt->execute(['project_id' => $projectId]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createBudgetItem(array $data): void
    {
        $sql = "INSERT INTO budget_items (project_id, nombre_partida, categoria, unidad_medida, cantidad_estimada, precio_unitario)
                VALUES (:project_id, :nombre_partida, :categoria, :unidad_medida, :cantidad_estimada, :precio_unitario)";
        $this->pdo->prepare($sql)->execute([
            'project_id'        => $data['project_id'],
            'nombre_partida'    => $data['nombre_partida'],
            'categoria'         => $data['categoria'],
            'unidad_medida'     => $data['unidad_medida'],
            'cantidad_estimada' => $data['cantidad_estimada'],
            'precio_unitario'   => $data['precio_unitario']
        ]);
    }

    public function findEstimations(): array
    {
        $stmt = $this->pdo->query("
            SELECT e.*, p.nombre as project_name 
            FROM estimations e 
            LEFT JOIN projects p ON e.project_id = p.id 
            ORDER BY e.created_at DESC
        ");
        $estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $itemStmt = $this->pdo->prepare("
            SELECT ei.*, bi.nombre_partida, bi.cantidad_estimada, bi.precio_unitario, bi.unidad_medida
            FROM estimation_items ei
            JOIN budget_items bi ON ei.budget_item_id = bi.id
            WHERE ei.estimation_id = :estimation_id
        ");

        foreach ($estimations as &$est) {
            $itemStmt->execute(['estimation_id' => $est['id']]);
            $est['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $estimations;
    }

    public function createEstimation(array $data): int
    {
        $sql = "INSERT INTO estimations (project_id, periodo, observaciones, estado)
                VALUES (:project_id, :periodo, :observaciones, 'En Revisión')";
        $this->pdo->prepare($sql)->execute([
            'project_id'    => $data['project_id'],
            'periodo'       => $data['periodo'],
            'observaciones' => $data['observaciones'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createEstimationItem(int $estimationId, array $item): void
    {
        $sql = "INSERT INTO estimation_items (estimation_id, budget_item_id, porcentaje_avance)
                VALUES (:estimation_id, :budget_item_id, :porcentaje_avance)";
        $this->pdo->prepare($sql)->execute([
            'estimation_id'     => $estimationId,
            'budget_item_id'    => $item['budget_item_id'],
            'porcentaje_avance' => $item['porcentaje_avance'] ?? 0
        ]);
    }

    public function getSummary(): array
    {
        $stmt = $this->pdo->query("
            SELECT p.id as project_id, p.nombre as project_name, 
                   COALESCE(SUM(bi.cantidad_estimada * bi.precio_unitario), 0) as total_budget
            FROM projects p
            LEFT JOIN budget_items bi ON p.id = bi.project_id
            GROUP BY p.id
            HAVING total_budget > 0
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
