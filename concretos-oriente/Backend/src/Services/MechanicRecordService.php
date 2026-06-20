<?php
namespace App\Services;

use App\Repositories\MechanicRecordRepository;
use App\Utils\Uploader;
use Exception;

class MechanicRecordService
{
    private MechanicRecordRepository $repo;

    public function __construct()
    {
        $this->repo = new MechanicRecordRepository();
    }

    public function getAll(): array
    {
        return $this->repo->findAllWithDetails();
    }

    public function getAllPlates(): array
    {
        return $this->repo->getAllPlates();
    }

    public function getItems(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new Exception('Registro no encontrado.', 404);
        }
        return $this->repo->findItemsByRecordId($id);
    }

    public function create(array $data, array $files): array
    {
        $this->validate($data);
        $items = $this->parseItems($data['items_json'] ?? '[]');

        $this->repo->getPDO()->beginTransaction();
        try {
            $id = $this->repo->create($data);
            foreach ($items as $item) {
                $this->repo->createItem($id, $item['producto'], (float) $item['monto']);
            }
            $this->handlePhotoUploads($id, $files);
            $this->repo->getPDO()->commit();
            return ['success' => true, 'id' => $id, 'message' => 'Registro de mecánica guardado correctamente.'];
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
        $items = $this->parseItems($data['items_json'] ?? '[]');

        $this->repo->getPDO()->beginTransaction();
        try {
            $this->repo->update($id, $data);
            $this->repo->deleteItems($id);
            foreach ($items as $item) {
                $this->repo->createItem($id, $item['producto'], (float) $item['monto']);
            }
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
        if (empty($data['fecha']) || empty($data['placa']) || empty($data['tipo_unidad'])) {
            throw new Exception('Fecha, unidad y tipo de unidad son obligatorios.', 400);
        }
    }

    private function parseItems(string $json): array
    {
        $items = json_decode($json, true);
        if (!is_array($items)) return [];
        return array_filter($items, fn($i) => !empty($i['producto']));
    }

    private function handlePhotoUploads(int $id, array $files): void
    {
        $uploader = new Uploader('Uploads/MechanicRecords/' . $id);
        $photos   = [];
        foreach (['foto_1','foto_2','foto_3','foto_4','foto_5'] as $field) {
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
        $dir = __DIR__ . '/../../Uploads/MechanicRecords/' . $id . '/';
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($dir);
        }
    }
}
