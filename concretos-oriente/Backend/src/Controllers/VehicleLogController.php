<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\VehicleLogService;
use Exception;

class VehicleLogController extends Controller
{
    private VehicleLogService $logService;

    public function __construct()
    {
        $this->logService = new VehicleLogService();
    }

    // ----------------------------------------------------------------
    // POST /vehicle-logs  — registrar movimiento en bitácora
    // ----------------------------------------------------------------
    #[Route('/vehicle-logs', 'POST')]
    public function store()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $data = [
                'vehiculo_id'      => $input['vehiculo_id'] ?? null,
                'piloto_id'        => !empty($input['piloto_id']) ? (int)$input['piloto_id'] : null,
                'estatus_vehiculo' => trim($input['estatus_vehiculo'] ?? ''),
                'envio_servicio'   => !empty(trim($input['envio_servicio'] ?? '')) ? trim($input['envio_servicio']) : null,
                'reportar_averia'  => !empty(trim($input['reportar_averia'] ?? '')) ? trim($input['reportar_averia']) : null,
                'observaciones'    => !empty(trim($input['observaciones'] ?? '')) ? trim($input['observaciones']) : null,
            ];

            $result = $this->logService->createLog($data);

            $this->json([
                'success' => true,
                'message' => 'Bitácora registrada y vehículo actualizado correctamente.',
                'id'      => $result['id']
            ], 201);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /vehicle-logs/{vehiculo_id}  — obtener historial por vehículo
    // ----------------------------------------------------------------
    #[Route('/vehicle-logs/{vehiculo_id}', 'GET')]
    public function getByVehicle($vehiculo_id)
    {
        try {
            $logs = $this->logService->getLogsByVehicle((int)$vehiculo_id);

            $this->json([
                'success' => true,
                'data'    => $logs
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
