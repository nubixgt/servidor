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

        if (empty($data['estatus_vehiculo'])) {
            throw new Exception('El estatus del vehículo es obligatorio.', 400);
        }

        return $this->logRepository->create($data);
    }

    public function getLogsByVehicle(int $vehiculoId): array
    {
        return $this->logRepository->getLogsByVehicle($vehiculoId);
    }
}
