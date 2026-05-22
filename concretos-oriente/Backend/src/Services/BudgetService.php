<?php
namespace App\Services;

use App\Repositories\BudgetRepository;
use Exception;

class BudgetService
{
    private BudgetRepository $budgetRepository;

    public function __construct()
    {
        $this->budgetRepository = new BudgetRepository();
    }

    public function getBudgetItems(?int $projectId): array
    {
        return $this->budgetRepository->findBudgetItems($projectId);
    }

    public function createBudgetItem(array $data): void
    {
        if (empty($data['project_id']) || empty($data['nombre_partida']) || empty($data['categoria'])) {
            throw new Exception('Proyecto, nombre de partida y categoría son obligatorios', 400);
        }

        $this->budgetRepository->createBudgetItem($data);
    }

    public function getEstimations(): array
    {
        $estimations = $this->budgetRepository->findEstimations();

        foreach ($estimations as &$est) {
            $total_cost = 0;
            foreach ($est['items'] as $item) {
                $itemTotal = ((float)$item['cantidad_estimada'] * (float)$item['precio_unitario']);
                $total_cost += $itemTotal * ((float)$item['porcentaje_avance'] / 100);
            }
            $est['costo_calculado'] = $total_cost;
        }

        return $estimations;
    }

    public function createEstimation(array $data): void
    {
        if (empty($data['project_id']) || empty($data['periodo'])) {
            throw new Exception('Proyecto y periodo son obligatorios', 400);
        }

        $items = $data['items'] ?? [];

        $pdo = $this->budgetRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $estimation_id = $this->budgetRepository->createEstimation($data);

            foreach ($items as $item) {
                $this->budgetRepository->createEstimationItem($estimation_id, $item);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getSummary(): array
    {
        return $this->budgetRepository->getSummary();
    }
}
