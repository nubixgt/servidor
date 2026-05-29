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
        $frontalPath = null;
        $traseraPath = null;
        
        $uploader = new Uploader('Uploads/Vehicles/' . $vehicleId);

        if (isset($files['foto_frontal']) && $files['foto_frontal']['error'] === UPLOAD_ERR_OK) {
            $frontalPath = $uploader->upload($files['foto_frontal'], 'foto_frontal');
        }

        if (isset($files['foto_trasera']) && $files['foto_trasera']['error'] === UPLOAD_ERR_OK) {
            $traseraPath = $uploader->upload($files['foto_trasera'], 'foto_trasera');
        }

        if ($frontalPath || $traseraPath) {
            $this->vehicleRepo->updatePhotos($vehicleId, $frontalPath, $traseraPath);
        }
    }
}
