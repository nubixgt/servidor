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
        if (empty($data['nombre']) || empty($data['tipo_maquinaria']) || empty($data['subtipo'])) {
            throw new Exception('Nombre, tipo de maquinaria y subtipo son obligatorios.', 400);
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
    }

    private function deletePhotoFolder(int $id): void
    {
        $dir = __DIR__ . '/../../Uploads/SpecialMachinery/' . $id . '/';
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($dir);
        }
    }
}
