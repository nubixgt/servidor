<?php
namespace App\Services;

use App\Repositories\MachineryRepository;
use App\Repositories\MachineryLogRepository;
use Exception;

class MachineryService
{
    private MachineryRepository $machineryRepository;
    private MachineryLogRepository $machineryLogRepository;

    public function __construct()
    {
        $this->machineryRepository = new MachineryRepository();
        $this->machineryLogRepository = new MachineryLogRepository();
    }

    public function getAllMachinery(?array $user = null): array
    {
        return $this->machineryRepository->findAllWithDetails($user);
    }

    public function createMachinery(array $data, ?array $fileData): array
    {
        $this->validateMachineryData($data);

        $pdo = $this->machineryRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->machineryRepository->create($data);
            $foto_path = null;

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $foto_path = $this->handlePhotoUpload($newId, $fileData);
                if ($foto_path) {
                    $this->machineryRepository->updatePhotoPath($newId, $foto_path);
                }
            }

            $pdo->commit();

            return [
                'id' => $newId,
                'foto_path' => $foto_path
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateMachinery(int $id, array $data, ?array $fileData): array
    {
        $maquina = $this->machineryRepository->findById($id);
        if (!$maquina) {
            throw new Exception('Maquinaria no encontrada', 404);
        }

        $this->validateMachineryData($data);

        $pdo = $this->machineryRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $this->machineryRepository->update($id, $data);
            $foto_path = $maquina['foto_path'];

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $new_foto_path = $this->handlePhotoUpload($id, $fileData, true);
                if ($new_foto_path) {
                    $foto_path = $new_foto_path;
                    $this->machineryRepository->updatePhotoPath($id, $foto_path);
                }
            }

            $pdo->commit();

            return [
                'foto_path' => $foto_path
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function deleteMachinery(int $id): void
    {
        $maquina = $this->machineryRepository->findById($id);
        if (!$maquina) {
            throw new Exception('Maquinaria no encontrada', 404);
        }

        $pdo = $this->machineryRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $this->deletePhotoFolder($id);
            // Primero se deben eliminar las bitácoras dependientes
            $this->machineryLogRepository->deleteByMachineryId($id);
            // Luego la maquinaria
            $this->machineryRepository->delete($id);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getAllLogs(?array $user = null): array
    {
        return $this->machineryLogRepository->findAllWithDetails($user);
    }

    public function createLog(array $data): array
    {
        $this->validateLogData($data);

        $pdo = $this->machineryRepository->getPDO(); // Use the same PDO connection
        $pdo->beginTransaction();

        try {
            $newId = $this->machineryLogRepository->create($data);

            // Actualizar horómetro de la máquina si el final es mayor al actual
            $this->machineryRepository->updateHorometroIfGreater(
                (int)$data['maquina_id'], 
                (int)$data['horometro_final']
            );

            $pdo->commit();

            return ['id' => $newId];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function deleteLog(int $id): void
    {
        $log = $this->machineryLogRepository->findById($id);
        if (!$log) {
            throw new Exception('Bitácora no encontrada', 404);
        }

        $this->machineryLogRepository->delete($id);
    }

    private function validateMachineryData(array $data): void
    {
        if (
            empty($data['categoria']) || empty($data['codigo_interno']) ||
            empty($data['marca'])     || empty($data['modelo'])          ||
            $data['horometro_actual'] === null || $data['horometro_actual'] === ''
        ) {
            throw new Exception('Los campos categoría, código interno, marca, modelo y horómetro son obligatorios.', 400);
        }
    }

    private function validateLogData(array $data): void
    {
        if (
            empty($data['maquina_id']) || empty($data['fecha']) ||
            $data['horometro_inicial'] === null || $data['horometro_inicial'] === '' ||
            $data['horometro_final']   === null || $data['horometro_final']   === ''
        ) {
            throw new Exception('Los campos máquina, fecha, horómetro inicial y final son obligatorios.', 400);
        }
    }

    private function handlePhotoUpload(int $id, array $fileData, bool $cleanOld = false): ?string
    {
        $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/';

        if ($cleanOld && is_dir($uploadDir)) {
            foreach (glob($uploadDir . '*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } elseif (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath   = $fileData['tmp_name'];
        $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array($fileExtension, $allowed)) {
            $newFileName = 'foto.' . $fileExtension;
            $destPath    = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                return 'Uploads/Machinery/' . $id . '/' . $newFileName;
            }
        }
        
        return null;
    }

    private function deletePhotoFolder(int $id): void
    {
        $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/';
        if (is_dir($uploadDir)) {
            foreach (glob($uploadDir . '*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($uploadDir);
        }
    }
}
