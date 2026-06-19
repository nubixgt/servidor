<?php
namespace App\Services;

use App\Repositories\VehicleRepository;
use App\Utils\Uploader;
use Exception;

class VehicleService
{
    private VehicleRepository $vehicleRepo;

    public function __construct()
    {
        $this->vehicleRepo = new VehicleRepository();
    }

    public function getAllVehicles(): array
    {
        return $this->vehicleRepo->findAllWithDetails();
    }

    public function getVehicleById(int $id): ?array
    {
        return $this->vehicleRepo->findById($id);
    }

    public function createVehicle(array $data, array $files): array
    {
        $this->vehicleRepo->getPDO()->beginTransaction();

        try {
            $vehicleId = $this->vehicleRepo->create($data);
            $this->handlePhotoUploads($vehicleId, $files);
            $this->vehicleRepo->getPDO()->commit();
            return ['success' => true, 'id' => $vehicleId, 'message' => 'Vehículo registrado correctamente.'];
        } catch (Exception $e) {
            $this->vehicleRepo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function updateVehicle(int $id, array $data, array $files): array
    {
        $this->vehicleRepo->getPDO()->beginTransaction();

        try {
            $this->vehicleRepo->update($id, $data);
            $this->handlePhotoUploads($id, $files);
            $this->vehicleRepo->getPDO()->commit();
            return ['success' => true, 'message' => 'Vehículo actualizado correctamente.'];
        } catch (Exception $e) {
            $this->vehicleRepo->getPDO()->rollBack();
            throw $e;
        }
    }

    public function deleteVehicle(int $id): array
    {
        $this->vehicleRepo->delete($id);
        return ['success' => true, 'message' => 'Vehículo eliminado correctamente.'];
    }

    private function handlePhotoUploads(int $vehicleId, array $files): void
    {
        $uploader = new Uploader('Uploads/Vehicles/' . $vehicleId);
        $photos   = [];

        $fields = ['foto_delantera', 'foto_trasera', 'foto_lateral1', 'foto_lateral2'];

        foreach ($fields as $field) {
            if (isset($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
                $photos[$field] = $uploader->upload($files[$field], $field);
            }
        }

        if (!empty($photos)) {
            $this->vehicleRepo->updatePhotos($vehicleId, $photos);
        }
    }
}
