<?php
namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class MaintenanceController {
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

    #[Route('/maintenance/machinery', 'GET')]
    public function getMachinery() {
        try {
            $stmt = $this->db->query("SELECT id, codigo_interno, marca, modelo, categoria, horometro_actual FROM machinery ORDER BY codigo_interno ASC");
            $machinery = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->respond('success', 'Maquinaria', $machinery);
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/maintenance/logs', 'GET')]
    public function getLogs() {
        try {
            $stmt = $this->db->query("
                SELECT ml.*, m.codigo_interno, m.marca, m.modelo, p.nombres, p.apellidos 
                FROM maintenance_logs ml
                JOIN machinery m ON ml.machinery_id = m.id
                LEFT JOIN personnel p ON ml.responsable_id = p.id
                ORDER BY ml.fecha_mantenimiento DESC, ml.created_at DESC
            ");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get parts for each log
            foreach ($logs as &$log) {
                $stmtParts = $this->db->prepare("SELECT * FROM maintenance_parts WHERE maintenance_log_id = ?");
                $stmtParts->execute([$log['id']]);
                $log['repuestos'] = $stmtParts->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $this->respond('success', 'Bitacoras', $logs);
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/maintenance/logs', 'POST')]
    public function createLog() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $machinery_id = $data['machinery_id'] ?? '';
            $tipo_mantenimiento = $data['tipo_mantenimiento'] ?? 'Preventivo';
            $fecha_mantenimiento = $data['fecha_mantenimiento'] ?? '';
            $descripcion = $data['descripcion'] ?? '';
            $horometro_servicio = $data['horometro_servicio'] ?? 0;
            $responsable_id = !empty($data['responsable_id']) ? $data['responsable_id'] : null;
            $proximo_mantenimiento = !empty($data['proximo_mantenimiento']) ? $data['proximo_mantenimiento'] : null;
            $observaciones = $data['observaciones'] ?? '';
            $repuestos = $data['repuestos'] ?? [];
            
            $costo_total = 0;
            foreach ($repuestos as $rep) {
                $costo_total += ((float)$rep['cantidad'] * (float)$rep['costo_unitario']);
            }

            if (empty($machinery_id) || empty($fecha_mantenimiento) || empty($descripcion)) {
                return $this->respond('error', 'Faltan datos obligatorios');
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO maintenance_logs (
                    machinery_id, tipo_mantenimiento, fecha_mantenimiento, descripcion, 
                    costo_total, responsable_id, proximo_mantenimiento, horometro_servicio, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $machinery_id, $tipo_mantenimiento, $fecha_mantenimiento, $descripcion, 
                $costo_total, $responsable_id, $proximo_mantenimiento, $horometro_servicio, $observaciones
            ]);
            
            $log_id = $this->db->lastInsertId();

            if (!empty($repuestos)) {
                $stmtPart = $this->db->prepare("INSERT INTO maintenance_parts (maintenance_log_id, nombre_repuesto, cantidad, costo_unitario) VALUES (?, ?, ?, ?)");
                foreach ($repuestos as $rep) {
                    if (!empty($rep['nombre'])) {
                        $stmtPart->execute([$log_id, $rep['nombre'], $rep['cantidad'], $rep['costo_unitario']]);
                    }
                }
            }
            
            // Opcional: Actualizar horometro de la maquinaria si el reportado es mayor
            $stmtMach = $this->db->prepare("UPDATE machinery SET horometro_actual = GREATEST(horometro_actual, ?) WHERE id = ?");
            $stmtMach->execute([$horometro_servicio, $machinery_id]);

            $this->db->commit();
            $this->respond('success', 'Registro guardado exitosamente');
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }
}
