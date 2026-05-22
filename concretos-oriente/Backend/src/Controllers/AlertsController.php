<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\AlertService;
use Exception;

class AlertsController extends Controller
{
    private AlertService $alertService;

    public function __construct()
    {
        $this->alertService = new AlertService();
    }

    #[Route('/alerts_config', 'GET')]
    public function getConfigs()
    {
        try {
            $data = $this->alertService->getConfigs();
            $this->json(['status' => 'success', 'message' => 'Configuraciones recuperadas', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al recuperar configuraciones: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/alerts_config', 'POST')]
    public function createConfig()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $result = $this->alertService->createConfig($data);

            $this->json(['status' => 'success', 'message' => 'Configuración guardada exitosamente', 'data' => $result]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error al crear configuración: ' . $e->getMessage()], $code);
        }
    }

    #[Route('/alerts_config/{id}', 'DELETE')]
    public function deleteConfig($id)
    {
        try {
            $this->alertService->deleteConfig((int)$id);
            $this->json(['status' => 'success', 'message' => 'Configuración eliminada']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error al eliminar configuración: ' . $e->getMessage()], $code);
        }
    }

    #[Route('/alerts_history', 'GET')]
    public function getHistory()
    {
        try {
            $data = $this->alertService->getHistory();
            $this->json(['status' => 'success', 'message' => 'Historial recuperado', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al recuperar historial: ' . $e->getMessage()], 500);
        }
    }
}
