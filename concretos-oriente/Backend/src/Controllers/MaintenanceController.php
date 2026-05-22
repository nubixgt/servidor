<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\MaintenanceService;
use Exception;

class MaintenanceController extends Controller
{
    private MaintenanceService $maintenanceService;

    public function __construct()
    {
        $this->maintenanceService = new MaintenanceService();
    }

    #[Route('/maintenance/machinery', 'GET')]
    public function getMachinery()
    {
        try {
            $machinery = $this->maintenanceService->getMachineryList();
            $this->json(['status' => 'success', 'message' => 'Maquinaria', 'data' => $machinery]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/maintenance/logs', 'GET')]
    public function getLogs()
    {
        try {
            $logs = $this->maintenanceService->getAllLogs();
            $this->json(['status' => 'success', 'message' => 'Bitacoras', 'data' => $logs]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/maintenance/logs', 'POST')]
    public function createLog()
    {
        try {
            $data = [
                'machinery_id'          => $_POST['machinery_id'] ?? '',
                'tipo_mantenimiento'    => $_POST['tipo_mantenimiento'] ?? 'Preventivo',
                'fecha_mantenimiento'   => $_POST['fecha_mantenimiento'] ?? '',
                'descripcion'           => $_POST['descripcion'] ?? '',
                'horometro_servicio'    => $_POST['horometro_servicio'] ?? 0,
                'responsable_id'        => !empty($_POST['responsable_id']) ? $_POST['responsable_id'] : null,
                'proximo_mantenimiento' => !empty($_POST['proximo_mantenimiento']) ? $_POST['proximo_mantenimiento'] : null,
                'observaciones'         => $_POST['observaciones'] ?? '',
                'latitud'               => !empty($_POST['latitud']) ? $_POST['latitud'] : null,
                'longitud'              => !empty($_POST['longitud']) ? $_POST['longitud'] : null,
                'repuestos'             => $_POST['repuestos'] ?? null,
            ];

            $filesData = $_FILES['fotos'] ?? null;

            $this->maintenanceService->createLog($data, $filesData);

            $this->json(['status' => 'success', 'message' => 'Registro guardado exitosamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], $code);
        }
    }
}
