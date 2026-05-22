<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\BudgetService;
use Exception;

class BudgetsController extends Controller
{
    private BudgetService $budgetService;

    public function __construct()
    {
        $this->budgetService = new BudgetService();
    }

    // ----------------------------------------------------------------
    // Partidas Presupuestarias
    // ----------------------------------------------------------------

    #[Route('/budget-items', 'GET')]
    public function getBudgetItems()
    {
        try {
            $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
            $items = $this->budgetService->getBudgetItems($project_id);

            $this->json(['status' => 'success', 'message' => 'Partidas obtenidas', 'data' => $items]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al obtener partidas: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/budget-items', 'POST')]
    public function storeBudgetItem()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $itemData = [
                'project_id'        => $data['project_id'] ?? null,
                'nombre_partida'    => trim($data['nombre_partida'] ?? ''),
                'categoria'         => trim($data['categoria'] ?? ''),
                'unidad_medida'     => trim($data['unidad_medida'] ?? ''),
                'cantidad_estimada' => isset($data['cantidad_estimada']) ? (float)$data['cantidad_estimada'] : 0,
                'precio_unitario'   => isset($data['precio_unitario']) ? (float)$data['precio_unitario'] : 0,
            ];

            $this->budgetService->createBudgetItem($itemData);

            $this->json(['status' => 'success', 'message' => 'Partida registrada correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error al registrar partida: ' . $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // Estimaciones de Avance
    // ----------------------------------------------------------------

    #[Route('/estimations', 'GET')]
    public function getEstimations()
    {
        try {
            $estimations = $this->budgetService->getEstimations();
            $this->json(['status' => 'success', 'message' => 'Estimaciones obtenidas', 'data' => $estimations]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al obtener estimaciones: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/estimations', 'POST')]
    public function storeEstimation()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $estimationData = [
                'project_id'    => $data['project_id'] ?? null,
                'periodo'       => trim($data['periodo'] ?? ''),
                'observaciones' => trim($data['observaciones'] ?? ''),
                'items'         => $data['items'] ?? [], // Array of { budget_item_id, porcentaje_avance }
            ];

            $this->budgetService->createEstimation($estimationData);

            $this->json(['status' => 'success', 'message' => 'Estimación registrada correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error al registrar estimación: ' . $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // Totales Agrupados para Dashboard
    // ----------------------------------------------------------------
    
    #[Route('/budgets/summary', 'GET')]
    public function getSummary()
    {
        try {
            $projects_budget = $this->budgetService->getSummary();
            $this->json(['status' => 'success', 'message' => 'Resumen obtenido', 'data' => $projects_budget]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al obtener resumen: ' . $e->getMessage()], 500);
        }
    }
}
