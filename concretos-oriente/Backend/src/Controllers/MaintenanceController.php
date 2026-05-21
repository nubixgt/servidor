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
            
            // Get parts and photos for each log
            foreach ($logs as &$log) {
                $stmtParts = $this->db->prepare("SELECT * FROM maintenance_parts WHERE maintenance_log_id = ?");
                $stmtParts->execute([$log['id']]);
                $log['repuestos'] = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

                $stmtPhotos = $this->db->prepare("SELECT foto_path FROM maintenance_photos WHERE maintenance_log_id = ?");
                $stmtPhotos->execute([$log['id']]);
                $log['fotos'] = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);
            }
            
            $this->respond('success', 'Bitacoras', $logs);
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/maintenance/logs', 'POST')]
    public function createLog() {
        try {
            $data = $_POST;
            
            $machinery_id = $data['machinery_id'] ?? '';
            $tipo_mantenimiento = $data['tipo_mantenimiento'] ?? 'Preventivo';
            $fecha_mantenimiento = $data['fecha_mantenimiento'] ?? '';
            $descripcion = $data['descripcion'] ?? '';
            $horometro_servicio = $data['horometro_servicio'] ?? 0;
            $responsable_id = !empty($data['responsable_id']) ? $data['responsable_id'] : null;
            $proximo_mantenimiento = !empty($data['proximo_mantenimiento']) ? $data['proximo_mantenimiento'] : null;
            $observaciones = $data['observaciones'] ?? '';
            $latitud = !empty($data['latitud']) ? $data['latitud'] : null;
            $longitud = !empty($data['longitud']) ? $data['longitud'] : null;
            
            $repuestos = [];
            if (!empty($data['repuestos'])) {
                $repuestos = json_decode($data['repuestos'], true);
            }
            
            $costo_total = 0;
            if (is_array($repuestos)) {
                foreach ($repuestos as $rep) {
                    $costo_total += ((float)$rep['cantidad'] * (float)$rep['costo_unitario']);
                }
            }

            if (empty($machinery_id) || empty($fecha_mantenimiento) || empty($descripcion)) {
                return $this->respond('error', 'Faltan datos obligatorios');
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO maintenance_logs (
                    machinery_id, tipo_mantenimiento, fecha_mantenimiento, descripcion, 
                    costo_total, responsable_id, proximo_mantenimiento, horometro_servicio, observaciones, latitud, longitud
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $machinery_id, $tipo_mantenimiento, $fecha_mantenimiento, $descripcion, 
                $costo_total, $responsable_id, $proximo_mantenimiento, $horometro_servicio, $observaciones, $latitud, $longitud
            ]);
            
            $log_id = $this->db->lastInsertId();

            if (!empty($repuestos) && is_array($repuestos)) {
                $stmtPart = $this->db->prepare("INSERT INTO maintenance_parts (maintenance_log_id, nombre_repuesto, cantidad, costo_unitario) VALUES (?, ?, ?, ?)");
                foreach ($repuestos as $rep) {
                    if (!empty($rep['nombre'])) {
                        $stmtPart->execute([$log_id, $rep['nombre'], $rep['cantidad'], $rep['costo_unitario']]);
                    }
                }
            }
            
            // Handle file uploads
            if (!empty($_FILES['fotos']['name']) && is_array($_FILES['fotos']['name'])) {
                $baseDir = __DIR__ . '/../../Uploads/Maintenance/' . $log_id;
                if (!is_dir($baseDir)) {
                    mkdir($baseDir, 0777, true);
                }
                
                $stmtPhoto = $this->db->prepare("INSERT INTO maintenance_photos (maintenance_log_id, foto_path) VALUES (?, ?)");
                
                foreach ($_FILES['fotos']['name'] as $key => $name) {
                    if ($_FILES['fotos']['error'][$key] == UPLOAD_ERR_OK && !empty($name)) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fileName = 'foto_' . ($key + 1) . '_' . time() . '.' . $ext;
                        $destPath = $baseDir . '/' . $fileName;
                        
                        if (move_uploaded_file($_FILES['fotos']['tmp_name'][$key], $destPath)) {
                            $dbPath = 'Uploads/Maintenance/' . $log_id . '/' . $fileName;
                            $stmtPhoto->execute([$log_id, $dbPath]);
                        }
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
