<?php
namespace App\Services;

use App\Repositories\HeavyTransportRepository;
use App\Utils\Uploader;
use Exception;

class HeavyTransportService
{
    private HeavyTransportRepository $repo;

    public function __construct()
    {
        $this->repo = new HeavyTransportRepository();
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
            return ['success' => true, 'id' => $id, 'message' => 'Unidad registrada correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $files): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Unidad no encontrada.', 404);
        }

        $this->validate($data);
        $this->repo->getPDO()->beginTransaction();

        try {
            $this->repo->update($id, $data);
            $this->handlePhotoUploads($id, $files);
            $this->repo->getPDO()->commit();
            return ['success' => true, 'message' => 'Unidad actualizada correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Unidad no encontrada.', 404);
        }

        $this->deletePhotoFolder($id);
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Unidad eliminada correctamente.'];
    }

    private function validate(array $data): void
    {
        if (
            empty($data['placa']) || empty($data['tipo_transporte']) ||
            empty($data['marca']) || empty($data['modelo'])
        ) {
            throw new Exception('Placa, tipo de transporte, marca y modelo son obligatorios.', 400);
        }
    }

    private function handlePhotoUploads(int $id, array $files): void
    {
        $uploader = new Uploader('Uploads/HeavyTransport/' . $id);
        $photos   = [];

        foreach (['foto_delantera', 'foto_trasera', 'foto_lateral1', 'foto_lateral2'] as $field) {
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
        $dir = __DIR__ . '/../../Uploads/HeavyTransport/' . $id . '/';
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($dir);
        }
    }
}
