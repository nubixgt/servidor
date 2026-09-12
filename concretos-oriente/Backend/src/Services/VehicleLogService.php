<?php
namespace App\Services;

use App\Repositories\VehicleLogRepository;
use Exception;

class VehicleLogService
{
    private VehicleLogRepository $logRepository;

    public function __construct()
    {
        $this->logRepository = new VehicleLogRepository();
    }

    public function createLog(array $data): array
    {
        if (empty($data['vehiculo_id'])) {
            throw new Exception('El ID del vehículo es obligatorio.', 400);
        }

        // estatus_vehiculo ya no es estrictamente obligatorio si se usa para uso diario, 
        // pero se asume 'Activo' por defecto si no viene en el controller.

        return $this->logRepository->create($data);
    }

    public function getLogsByVehicle(int $vehiculoId): array
    {
        return $this->logRepository->getLogsByVehicle($vehiculoId);
    }

    public function getAllLogs(?array $user = null): array
    {
        return $this->logRepository->findAllWithDetails($user);
    }
}
