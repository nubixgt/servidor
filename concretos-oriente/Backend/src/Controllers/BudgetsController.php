<?php
namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class BudgetsController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function respond($status, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    // ----------------------------------------------------------------
    // Partidas Presupuestarias
    // ----------------------------------------------------------------

    #[Route('/budget-items', 'GET')]
    public function getBudgetItems() {
        try {
            $project_id = $_GET['project_id'] ?? null;
            $sql = "SELECT bi.*, p.nombre as project_name 
                    FROM budget_items bi 
                    LEFT JOIN projects p ON bi.project_id = p.id ";
            
            if ($project_id) {
                $sql .= " WHERE bi.project_id = :project_id ";
            }
            $sql .= " ORDER BY bi.created_at DESC";

            $stmt = $this->db->prepare($sql);
            if ($project_id) {
                $stmt->execute(['project_id' => $project_id]);
            } else {
                $stmt->execute();
            }

            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->respond('success', 'Partidas obtenidas', $items);
        } catch (\Exception $e) {
            $this->respond('error', 'Error al obtener partidas: ' . $e->getMessage());
        }
    }

    #[Route('/budget-items', 'POST')]
    public function storeBudgetItem() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $project_id = $data['project_id'] ?? null;
            $nombre_partida = $data['nombre_partida'] ?? '';
            $categoria = $data['categoria'] ?? '';
            $unidad_medida = $data['unidad_medida'] ?? '';
            $cantidad_estimada = $data['cantidad_estimada'] ?? 0;
            $precio_unitario = $data['precio_unitario'] ?? 0;

            if (!$project_id || empty($nombre_partida) || empty($categoria)) {
                return $this->respond('error', 'Proyecto, nombre de partida y categoría son obligatorios');
            }

            $stmt = $this->db->prepare("
                INSERT INTO budget_items (project_id, nombre_partida, categoria, unidad_medida, cantidad_estimada, precio_unitario)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $project_id, $nombre_partida, $categoria, $unidad_medida, $cantidad_estimada, $precio_unitario
            ]);

            $this->respond('success', 'Partida registrada correctamente');
        } catch (\Exception $e) {
            $this->respond('error', 'Error al registrar partida: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Estimaciones de Avance
    // ----------------------------------------------------------------

    #[Route('/estimations', 'GET')]
    public function getEstimations() {
        try {
            $stmt = $this->db->query("
                SELECT e.*, p.nombre as project_name 
                FROM estimations e 
                LEFT JOIN projects p ON e.project_id = p.id 
                ORDER BY e.created_at DESC
            ");
            $estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener items por estimación
            foreach ($estimations as &$est) {
                $itemStmt = $this->db->prepare("
                    SELECT ei.*, bi.nombre_partida, bi.cantidad_estimada, bi.precio_unitario, bi.unidad_medida
                    FROM estimation_items ei
                    JOIN budget_items bi ON ei.budget_item_id = bi.id
                    WHERE ei.estimation_id = ?
                ");
                $itemStmt->execute([$est['id']]);
                $est['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calcular costo total de esta estimación (porcentaje_avance * (cantidad * precio)) / 100
                $total_cost = 0;
                foreach ($est['items'] as $item) {
                    $itemTotal = ((float)$item['cantidad_estimada'] * (float)$item['precio_unitario']);
                    $total_cost += $itemTotal * ((float)$item['porcentaje_avance'] / 100);
                }
                $est['costo_calculado'] = $total_cost;
            }

            $this->respond('success', 'Estimaciones obtenidas', $estimations);
        } catch (\Exception $e) {
            $this->respond('error', 'Error al obtener estimaciones: ' . $e->getMessage());
        }
    }

    #[Route('/estimations', 'POST')]
    public function storeEstimation() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $project_id = $data['project_id'] ?? null;
            $periodo = $data['periodo'] ?? '';
            $observaciones = $data['observaciones'] ?? '';
            $items = $data['items'] ?? []; // Array of { budget_item_id, porcentaje_avance }

            if (!$project_id || empty($periodo)) {
                return $this->respond('error', 'Proyecto y periodo son obligatorios');
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO estimations (project_id, periodo, observaciones, estado)
                VALUES (?, ?, ?, 'En Revisión')
            ");
            $stmt->execute([$project_id, $periodo, $observaciones]);
            $estimation_id = $this->db->lastInsertId();

            $itemStmt = $this->db->prepare("
                INSERT INTO estimation_items (estimation_id, budget_item_id, porcentaje_avance)
                VALUES (?, ?, ?)
            ");

            foreach ($items as $item) {
                $itemStmt->execute([
                    $estimation_id,
                    $item['budget_item_id'],
                    $item['porcentaje_avance'] ?? 0
                ]);
            }

            $this->db->commit();

            $this->respond('success', 'Estimación registrada correctamente');
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->respond('error', 'Error al registrar estimación: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Totales Agrupados para Dashboard
    // ----------------------------------------------------------------
    
    #[Route('/budgets/summary', 'GET')]
    public function getSummary() {
        try {
            $stmt = $this->db->query("
                SELECT p.id as project_id, p.nombre as project_name, 
                       COALESCE(SUM(bi.cantidad_estimada * bi.precio_unitario), 0) as total_budget
                FROM projects p
                LEFT JOIN budget_items bi ON p.id = bi.project_id
                GROUP BY p.id
                HAVING total_budget > 0
            ");
            $projects_budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->respond('success', 'Resumen obtenido', $projects_budget);
        } catch (\Exception $e) {
            $this->respond('error', 'Error al obtener resumen: ' . $e->getMessage());
        }
    }
}
