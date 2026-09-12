<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\VehicleLogService;
use Exception;

class VehicleLogController extends Controller
{
    private VehicleLogService $logService;

    public function __construct()
    {
        $this->logService = new VehicleLogService();
    }

    #[Route('/vehicle-log', 'GET')]
    #[Authorize]
    public function index()
    {
        try {
            $user = $this->request->getAttribute('user');
            $logs = $this->logService->getAllLogs($user);

            $this->json([
                'status'  => 'success',
                'success' => true,
                'data'    => $logs
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/vehicle-logs', 'POST')]
    #[Authorize]
    public function store()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $user = $this->request->getAttribute('user');

            $data = [
                'vehiculo_id'           => $input['vehiculo_id'] ?? null,
                'piloto_id'             => !empty($input['piloto_id']) ? (int)$input['piloto_id'] : null,
                'estatus_vehiculo'      => trim($input['estatus_vehiculo'] ?? 'Activo'),
                'envio_servicio'        => !empty(trim($input['envio_servicio'] ?? '')) ? trim($input['envio_servicio']) : null,
                'reportar_averia'       => !empty(trim($input['reportar_averia'] ?? '')) ? trim($input['reportar_averia']) : null,
                'observaciones'         => !empty(trim($input['observaciones'] ?? '')) ? trim($input['observaciones']) : null,
                'fecha'                 => $input['fecha'] ?? null,
                'proyecto_id'           => !empty($input['proyecto_id']) ? (int)$input['proyecto_id'] : null,
                'kilometraje_inicial'   => isset($input['kilometraje_inicial']) ? (float)$input['kilometraje_inicial'] : 0,
                'kilometraje_final'     => isset($input['kilometraje_final']) ? (float)$input['kilometraje_final'] : 0,
                'combustible_consumido' => isset($input['combustible_consumido']) ? (float)$input['combustible_consumido'] : 0,
                'precio_renta'          => isset($input['precio_renta']) ? (float)$input['precio_renta'] : 0,
                'created_by'            => $user['id'] ?? null,
            ];

            $result = $this->logService->createLog($data);

            $this->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Bitácora registrada correctamente.',
                'id'      => $result['id']
            ], 201);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['success' => false, 'status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/vehicle-logs/{vehiculo_id}', 'GET')]
    #[Authorize]
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
