<?php
namespace App\Services;

use App\Repositories\PersonnelRepository;
use Exception;

class PersonnelService
{
    private PersonnelRepository $repository;

    public function __construct()
    {
        $this->repository = new PersonnelRepository();
    }

    public function getAllPersonnel(): array
    {
        return $this->repository->findAllWithProjects();
    }

    public function createPersonnel(array $data, ?array $fileData): array
    {
        $this->validatePersonnelData($data);

        $pdo = $this->repository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->repository->create($data);
            $foto_path = null;

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $foto_path = $this->handlePhotoUpload($newId, $fileData);
                if ($foto_path) {
                    $this->repository->updatePhotoPath($newId, $foto_path);
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

    public function updatePersonnel(int $id, array $data, ?array $fileData): array
    {
        $empleado = $this->repository->findById($id);
        if (!$empleado) {
            throw new Exception('Empleado no encontrado', 404);
        }

        $this->validatePersonnelData($data);

        $pdo = $this->repository->getPDO();
        $pdo->beginTransaction();

        try {
            $this->repository->update($id, $data);
            $foto_path = $empleado['foto_path'];

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $new_foto_path = $this->handlePhotoUpload($id, $fileData, true);
                if ($new_foto_path) {
                    $foto_path = $new_foto_path;
                    $this->repository->updatePhotoPath($id, $foto_path);
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

    public function deletePersonnel(int $id): void
    {
        $empleado = $this->repository->findById($id);
        if (!$empleado) {
            throw new Exception('Empleado no encontrado', 404);
        }

        $this->deletePhotoFolder($id);
        $this->repository->delete($id);
    }

    private function validatePersonnelData(array $data): void
    {
        if (
            empty($data['tipo_empleado']) || empty($data['nombres']) || empty($data['apellidos']) ||
            empty($data['dpi']) || empty($data['puesto']) || $data['salario_base'] === null || $data['salario_base'] === '' ||
            empty($data['tipo_planilla']) || empty($data['fecha_contratacion'])
        ) {
            throw new Exception('Los campos tipo_empleado, nombres, apellidos, dpi, puesto, salario_base, tipo_planilla y fecha_contratacion son obligatorios.', 400);
        }
    }

    private function handlePhotoUpload(int $id, array $fileData, bool $cleanOld = false): ?string
    {
        $uploadDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';

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
                return 'Uploads/Personal/' . $id . '/' . $newFileName;
            }
        }
        
        return null;
    }

    private function deletePhotoFolder(int $id): void
    {
        $uploadDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';
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
