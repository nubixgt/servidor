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

    public function createMachinery(array $data, ?array $filesData = null, ?array $seguroDoc = null): array
    {
        $this->validateMachineryData($data);

        $pdo = $this->machineryRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->machineryRepository->create($data);

            $paths = [];
            $photos = $this->handleMultiplePhotosUpload($newId, $filesData ?? []);
            if (!empty($photos)) {
                $paths['foto_path'] = $photos[0];
                $paths['fotos_json'] = json_encode($photos);
            }

            if ($seguroDoc && $seguroDoc['error'] === UPLOAD_ERR_OK) {
                $doc_path = $this->handleDocUpload($newId, $seguroDoc, 'contrato_seguro');
                if ($doc_path) {
                    $paths['seguro_contrato_adjunto_path'] = $doc_path;
                }
            }

            if (!empty($paths)) {
                $this->machineryRepository->updateDocumentPaths($newId, $paths);
            }

            $pdo->commit();

            return [
                'id' => $newId,
                'foto_path' => $paths['foto_path'] ?? null,
                'fotos_json' => $paths['fotos_json'] ?? null
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateMachinery(int $id, array $data, ?array $filesData = null, ?array $seguroDoc = null): array
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

            $paths = [];
            $newPhotos = $this->handleMultiplePhotosUpload($id, $filesData ?? []);
            if (!empty($newPhotos)) {
                $paths['foto_path'] = $newPhotos[0];
                $paths['fotos_json'] = json_encode($newPhotos);
            }

            if ($seguroDoc && $seguroDoc['error'] === UPLOAD_ERR_OK) {
                $doc_path = $this->handleDocUpload($id, $seguroDoc, 'contrato_seguro');
                if ($doc_path) {
                    $paths['seguro_contrato_adjunto_path'] = $doc_path;
                }
            }

            if (!empty($paths)) {
                $this->machineryRepository->updateDocumentPaths($id, $paths);
            }

            $pdo->commit();

            return [
                'id' => $id,
                'foto_path' => $paths['foto_path'] ?? $maquina['foto_path'],
                'fotos_json' => $paths['fotos_json'] ?? ($maquina['fotos_json'] ?? null)
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

    private function handleMultiplePhotosUpload(int $id, array $filesData): array
    {
        $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $savedPaths = [];
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        // 1. If $_FILES['fotos'] is an array of files (from <input multiple name="fotos[]" />)
        if (isset($filesData['fotos']) && is_array($filesData['fotos']['name'])) {
            $count = count($filesData['fotos']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($filesData['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($filesData['fotos']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $filename = 'foto_' . time() . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($filesData['fotos']['tmp_name'][$i], $uploadDir . $filename)) {
                            $savedPaths[] = 'Uploads/Machinery/' . $id . '/' . $filename;
                        }
                    }
                }
            }
        }

        // 2. If single file $_FILES['foto']
        if (isset($filesData['foto']) && is_array($filesData['foto']) && !is_array($filesData['foto']['name']) && $filesData['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($filesData['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'foto_' . time() . '_0.' . $ext;
                if (move_uploaded_file($filesData['foto']['tmp_name'], $uploadDir . $filename)) {
                    $savedPaths[] = 'Uploads/Machinery/' . $id . '/' . $filename;
                }
            }
        }

        // 3. Any key like 'foto_0', 'foto_1', ...
        foreach ($filesData as $key => $file) {
            if (str_starts_with($key, 'foto_') && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = $key . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $savedPaths[] = 'Uploads/Machinery/' . $id . '/' . $filename;
                    }
                }
            }
        }

        return $savedPaths;
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

    private function handleDocUpload(int $id, array $fileData, string $prefix): ?string
    {
        $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $fileData['tmp_name'];
        $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

        if (in_array($fileExtension, $allowed)) {
            $newFileName = $prefix . '_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                return 'Uploads/Machinery/' . $id . '/docs/' . $newFileName;
            }
        }
        return null;
    }

    private function deletePhotoFolder(int $id): void
    {
        $uploadDir = __DIR__ . '/../../Uploads/Machinery/' . $id . '/';
        if (is_dir($uploadDir)) {
            $this->rrmdir($uploadDir);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
}
