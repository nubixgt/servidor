<?php
namespace App\Services;

use App\Repositories\SpecialMachineryRepository;
use App\Utils\Uploader;
use Exception;

class SpecialMachineryService
{
    private SpecialMachineryRepository $repo;

    public function __construct()
    {
        $this->repo = new SpecialMachineryRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAllWithDetails();
    }

    public function create(array $data, array $files): array
    {
        $this->validate($data);
        $this->repo->getPDO()->beginTransaction();

        try {
            $id = $this->repo->create($data);
            $this->handlePhotoUploads($id, $files);
            $this->repo->getPDO()->commit();
            return ['success' => true, 'id' => $id, 'message' => 'Maquinaria registrada correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $files): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Maquinaria no encontrada.', 404);
        }

        $this->validate($data);
        $this->repo->getPDO()->beginTransaction();

        try {
            $this->repo->update($id, $data);
            $this->handlePhotoUploads($id, $files);
            $this->repo->getPDO()->commit();
            return ['success' => true, 'message' => 'Maquinaria actualizada correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Maquinaria no encontrada.', 404);
        }

        $this->deletePhotoFolder($id);
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Maquinaria eliminada correctamente.'];
    }

    private function validate(array $data): void
    {
        if (empty($data['nombre']) || empty($data['tipo_maquinaria'])) {
            throw new Exception('El nombre y el tipo de maquinaria son obligatorios.', 400);
        }
    }

    private function handlePhotoUploads(int $id, array $files): void
    {
        $uploader = new Uploader('Uploads/SpecialMachinery/' . $id);
        $photos   = [];

        foreach (['foto_1', 'foto_2', 'foto_3', 'foto_4', 'foto_5'] as $field) {
            if (isset($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
                $photos[$field] = $uploader->upload($files[$field], $field);
            }
        }

        if (!empty($photos)) {
            $this->repo->updatePhotos($id, $photos);
        }

        // Insurance contract upload
        if (isset($files['seguro_contrato_adjunto']) && $files['seguro_contrato_adjunto']['error'] === UPLOAD_ERR_OK) {
            $docPath = $this->handleDocUpload($id, $files['seguro_contrato_adjunto'], 'contrato_seguro');
            if ($docPath) {
                $this->repo->updateDocumentPaths($id, ['seguro_contrato_adjunto_path' => $docPath]);
            }
        }
    }

    private function handleDocUpload(int $id, array $fileData, string $prefix): ?string
    {
        $uploadDir = __DIR__ . '/../../Uploads/SpecialMachinery/' . $id . '/docs/';
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
                return 'Uploads/SpecialMachinery/' . $id . '/docs/' . $newFileName;
            }
        }
        return null;
    }

    private function deletePhotoFolder(int $id): void
    {
        $dir = __DIR__ . '/../../Uploads/SpecialMachinery/' . $id . '/';
        if (is_dir($dir)) {
            $this->rrmdir($dir);
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
