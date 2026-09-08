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

    public function createPersonnel(array $data, array $files = []): array
    {
        $this->validatePersonnelData($data);

        $pdo = $this->repository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->repository->create($data);
            $docPaths = [];

            if (!empty($files['foto']) && $files['foto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($newId, $files['foto'], 'foto', ['jpg', 'jpeg', 'png']);
                if ($p) $docPaths['foto_path'] = $p;
            }
            if (!empty($files['dpi_adjunto']) && $files['dpi_adjunto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($newId, $files['dpi_adjunto'], 'dpi', ['jpg', 'jpeg', 'png', 'pdf']);
                if ($p) $docPaths['dpi_adjunto_path'] = $p;
            }
            if (!empty($files['contrato_adjunto']) && $files['contrato_adjunto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($newId, $files['contrato_adjunto'], 'contrato', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
                if ($p) $docPaths['contrato_adjunto_path'] = $p;
            }
            if (!empty($files['licencia_adjunto']) && $files['licencia_adjunto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($newId, $files['licencia_adjunto'], 'licencia', ['jpg', 'jpeg', 'png', 'pdf']);
                if ($p) $docPaths['licencia_adjunto_path'] = $p;
            }

            if (!empty($docPaths)) {
                $this->repository->updateDocumentPaths($newId, $docPaths);
            }

            $pdo->commit();

            return array_merge(['id' => $newId], $docPaths);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updatePersonnel(int $id, array $data, array $files = []): array
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
            $docPaths = [];

            if (!empty($files['foto']) && $files['foto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($id, $files['foto'], 'foto', ['jpg', 'jpeg', 'png'], true);
                if ($p) $docPaths['foto_path'] = $p;
            }
            if (!empty($files['dpi_adjunto']) && $files['dpi_adjunto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($id, $files['dpi_adjunto'], 'dpi', ['jpg', 'jpeg', 'png', 'pdf'], true);
                if ($p) $docPaths['dpi_adjunto_path'] = $p;
            }
            if (!empty($files['contrato_adjunto']) && $files['contrato_adjunto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($id, $files['contrato_adjunto'], 'contrato', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'], true);
                if ($p) $docPaths['contrato_adjunto_path'] = $p;
            }
            if (!empty($files['licencia_adjunto']) && $files['licencia_adjunto']['error'] === UPLOAD_ERR_OK) {
                $p = $this->handleFileUpload($id, $files['licencia_adjunto'], 'licencia', ['jpg', 'jpeg', 'png', 'pdf'], true);
                if ($p) $docPaths['licencia_adjunto_path'] = $p;
            }

            if (!empty($docPaths)) {
                $this->repository->updateDocumentPaths($id, $docPaths);
            }

            $pdo->commit();

            return $docPaths;
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

    private function handleFileUpload(int $id, array $fileData, string $prefix = 'doc', array $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'], bool $cleanPrefix = false): ?string
    {
        $uploadDir = __DIR__ . '/../../Uploads/Personal/' . $id . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($cleanPrefix && is_dir($uploadDir)) {
            foreach (glob($uploadDir . $prefix . '.*') as $oldFile) {
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
        }

        $fileTmpPath   = $fileData['tmp_name'];
        $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedExts)) {
            $newFileName = $prefix . '.' . $fileExtension;
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
