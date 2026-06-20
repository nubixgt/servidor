<?php
namespace App\Services;

use App\Repositories\FuelRecordRepository;
use App\Utils\Uploader;
use Exception;

class FuelRecordService
{
    private FuelRecordRepository $repo;

    public function __construct()
    {
        $this->repo = new FuelRecordRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAllWithDetails();
    }

    public function getAllPlates(): array
    {
        return $this->repo->getAllPlates();
    }

    public function create(array $data, array $files): array
    {
        $this->validate($data);
        $this->repo->getPDO()->beginTransaction();

        try {
            $id = $this->repo->create($data);
            $this->handlePhotoUploads($id, $files);
            $this->repo->getPDO()->commit();
            return ['success' => true, 'id' => $id, 'message' => 'Registro de combustible guardado correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, array $files): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Registro no encontrado.', 404);
        }

        $this->validate($data);
        $this->repo->getPDO()->beginTransaction();

        try {
            $this->repo->update($id, $data);
            $this->handlePhotoUploads($id, $files);
            $this->repo->getPDO()->commit();
            return ['success' => true, 'message' => 'Registro actualizado correctamente.'];
        } catch (Exception $e) {
            $this->repo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Registro no encontrado.', 404);
        }

        $this->deletePhotoFolder($id);
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Registro eliminado correctamente.'];
    }

    private function validate(array $data): void
    {
        if (
            empty($data['fecha']) || empty($data['placa']) ||
            empty($data['tipo_unidad']) ||
            ($data['cantidad_galones'] === null || $data['cantidad_galones'] === '') ||
            ($data['monto'] === null || $data['monto'] === '')
        ) {
            throw new Exception('Fecha, placa, tipo de unidad, galones y monto son obligatorios.', 400);
        }
    }

    private function handlePhotoUploads(int $id, array $files): void
    {
        $uploader = new Uploader('Uploads/FuelRecords/' . $id);
        $photos   = [];

        foreach (['foto_1', 'foto_2'] as $field) {
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
        $dir = __DIR__ . '/../../Uploads/FuelRecords/' . $id . '/';
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($dir);
        }
    }
}
