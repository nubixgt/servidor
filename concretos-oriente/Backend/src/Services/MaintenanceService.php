<?php
namespace App\Services;

use App\Repositories\MaintenanceRepository;
use Exception;

class MaintenanceService
{
    private MaintenanceRepository $maintenanceRepository;

    public function __construct()
    {
        $this->maintenanceRepository = new MaintenanceRepository();
    }

    public function getMachineryList(): array
    {
        return $this->maintenanceRepository->getMachineryList();
    }

    public function getAllLogs(): array
    {
        return $this->maintenanceRepository->findAllLogsWithDetails();
    }

    public function createLog(array $data, ?array $filesData): void
    {
        if (empty($data['machinery_id']) || empty($data['fecha_mantenimiento']) || empty($data['descripcion'])) {
            throw new Exception('Faltan datos obligatorios', 400);
        }

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
        $data['costo_total'] = $costo_total;

        $pdo = $this->maintenanceRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $log_id = $this->maintenanceRepository->createLog($data);

            if (!empty($repuestos) && is_array($repuestos)) {
                foreach ($repuestos as $rep) {
                    if (!empty($rep['nombre'])) {
                        $this->maintenanceRepository->createPart($log_id, $rep);
                    }
                }
            }

            $uploadedPhotos = [];
            if ($filesData && !empty($filesData['name']) && is_array($filesData['name'])) {
                $baseDir = __DIR__ . '/../../Uploads/Maintenance/' . $log_id;
                if (!is_dir($baseDir)) {
                    mkdir($baseDir, 0777, true);
                }
                
                foreach ($filesData['name'] as $key => $name) {
                    if ($filesData['error'][$key] == UPLOAD_ERR_OK && !empty($name)) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fileName = 'foto_' . ($key + 1) . '_' . time() . '.' . $ext;
                        $destPath = $baseDir . '/' . $fileName;
                        
                        if (move_uploaded_file($filesData['tmp_name'][$key], $destPath)) {
                            $dbPath = 'Uploads/Maintenance/' . $log_id . '/' . $fileName;
                            $uploadedPhotos[] = $dbPath;
                        }
                    }
                }
            }

            if (!empty($uploadedPhotos)) {
                $this->maintenanceRepository->updatePhotos($log_id, json_encode($uploadedPhotos));
            }

            if (!empty($data['horometro_servicio'])) {
                $this->maintenanceRepository->updateMachineryHorometer((int)$data['machinery_id'], (float)$data['horometro_servicio']);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
