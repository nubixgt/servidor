<?php
namespace App\Controllers\VIDER;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\VIDER\ViderService;
use App\DTOs\VIDER\EjecucionDTO;
use App\DTOs\VIDER\TobanikDTO;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_vider')]
class ViderController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new ViderService();
    }

    #[Route('/vider/dashboard', 'GET')]
    public function dashboard()
    {
        try {
            $action = $_GET['action'] ?? null;
            $filters = $_GET;
            unset($filters['action']);

            switch ($action) {
                case 'stats':
                    $data = $this->service->getDashboardData($filters);
                    $this->json(['success' => true, 'stats' => $data]);
                    break;
                case 'map':
                    $data = $this->service->getMapData($filters);
                    $this->json(['success' => true, 'data' => $data]);
                    break;
                case 'advanced_catalogos':
                    $depId = $_GET['dependencia_id'] ?? null;
                    $data = $this->service->getCatalogos($depId);
                    $this->json(['success' => true, 'data' => $data]);
                    break;
                case 'tobanik':
                    $data = $this->service->getTobanikData($filters);
                    $this->json(['success' => true, 'data' => $data]);
                    break;
                case 'records':
                    $data = $this->service->getRecords($filters);
                    $this->json(['success' => true, 'data' => $data]);
                    break;
                default:
                    $this->json(['success' => false, 'message' => 'Acción no válida'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/vider/ejecucion', 'POST')]
    public function createEjecucion()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = EjecucionDTO::fromRequest($data);
            $id = $this->service->createEjecucion($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Ejecución VIDER", "Se ha registrado un nuevo ítem de ejecución presupuestaria en VIDER.", "success"
            );
            $this->json(['success' => true, 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/vider/ejecucion/{id}', 'PUT')]
    public function updateEjecucion($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = EjecucionDTO::fromRequest($data);
            $this->service->updateEjecucion($id, $dto->toArray());
            $this->json(['success' => true, 'message' => 'Ejecución actualizada']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/vider/ejecucion/{id}', 'DELETE')]
    public function deleteEjecucion($id)
    {
        try {
            $this->service->deleteEjecucion($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Ejecución Eliminada", "Se ha eliminado un registro de ejecución en VIDER.", "warning"
            );
            $this->json(['success' => true, 'message' => 'Ejecución eliminada']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/vider/tobanik', 'POST')]
    public function createTobanik()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = TobanikDTO::fromRequest($data);
            $id = $this->service->createTobanik($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Registro Tobanik", "Se ha registrado un nuevo dato en el programa Tobanik.", "success"
            );
            $this->json(['success' => true, 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/vider/tobanik/{id}', 'PUT')]
    public function updateTobanik($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = TobanikDTO::fromRequest($data);
            $this->service->updateTobanik($id, $dto->toArray());
            $this->json(['success' => true, 'message' => 'Tobanik actualizado']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/vider/tobanik/{id}', 'DELETE')]
    public function deleteTobanik($id)
    {
        try {
            $this->service->deleteTobanik($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Registro Tobanik Eliminado", "Se ha eliminado un registro del programa Tobanik.", "warning"
            );
            $this->json(['success' => true, 'message' => 'Tobanik eliminado']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
