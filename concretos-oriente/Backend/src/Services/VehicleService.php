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
            $this->handlePhotoAndDocUploads($vehicleId, $files);
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
            $this->handlePhotoAndDocUploads($id, $files);
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

    private function handlePhotoAndDocUploads(int $vehicleId, array $files): void
    {
        $uploader = new Uploader('Uploads/Vehicles/' . $vehicleId);
        $updates  = [];

        $fields = [
            'foto_delantera'             => 'foto_delantera',
            'foto_trasera'               => 'foto_trasera',
            'foto_lateral1'              => 'foto_lateral1',
            'foto_lateral2'              => 'foto_lateral2',
            'seguro_contrato_adjunto'    => 'seguro_contrato_adjunto_path',
            'calcomania_adjunto'         => 'calcomania_adjunto_path',
            'titulo_propiedad_adjunto'   => 'titulo_propiedad_adjunto_path',
            'tarjeta_circulacion_adjunto'=> 'tarjeta_circulacion_adjunto_path',
        ];

        foreach ($fields as $inputKey => $dbCol) {
            if (isset($files[$inputKey]) && $files[$inputKey]['error'] === UPLOAD_ERR_OK) {
                $updates[$dbCol] = $uploader->upload($files[$inputKey], $inputKey);
            }
        }

        if (!empty($updates)) {
            $this->vehicleRepo->updatePhotos($vehicleId, $updates);
        }
    }
}
