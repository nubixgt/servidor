<?php
namespace App\Controllers\VISAR;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\HasPrivilege;
use App\Services\VISAR\VisarService;
use App\DTOs\VISAR\InspeccionDTO;
use App\DTOs\VISAR\LicenciaDTO;

#[HasPrivilege('modulo_visar')]
class VisarController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new VisarService();
    }

    // --- INSPECCIONES ---
    #[Route('/visar/inspecciones', 'GET')]
    public function indexInspecciones()
    {
        try {
            $filters = [
                'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
                'area' => filter_input(INPUT_GET, 'area', FILTER_SANITIZE_SPECIAL_CHARS) ?: null
            ];
            $data = $this->service->getInspecciones($filters);
            $stats = $this->service->getInspeccionStats();
            $this->json(['status' => 'success', 'data' => $data, 'stats' => $stats]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/visar/inspecciones', 'POST')]
    public function createInspeccion()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = InspeccionDTO::fromRequest($data);
            $id = $this->service->createInspeccion($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Inspección", "Se ha registrado una nueva inspección en VISAR.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visar/inspecciones/{id}', 'PUT')]
    public function updateInspeccion($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = InspeccionDTO::fromRequest($data);
            $this->service->updateInspeccion($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Inspección actualizada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visar/inspecciones/{id}', 'DELETE')]
    public function deleteInspeccion($id)
    {
        try {
            $this->service->deleteInspeccion($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Inspección Eliminada", "Se ha eliminado un registro de inspección en VISAR.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Inspección eliminada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // --- LICENCIAS ---
    #[Route('/visar/licencias', 'GET')]
    public function indexLicencias()
    {
        try {
            $filters = [
                'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
                'estado' => filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_NUMBER_INT) ?: null
            ];
            $data = $this->service->getLicencias($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/visar/licencias', 'POST')]
    public function createLicencia()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = LicenciaDTO::fromRequest($data);
            $id = $this->service->createLicencia($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Licencia Emitida", "Se ha emitido una nueva licencia en VISAR.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visar/licencias/{id}', 'PUT')]
    public function updateLicencia($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = LicenciaDTO::fromRequest($data);
            $this->service->updateLicencia($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Licencia actualizada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/visar/licencias/{id}', 'DELETE')]
    public function deleteLicencia($id)
    {
        try {
            $this->service->deleteLicencia($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Licencia Eliminada", "Se ha eliminado una licencia en VISAR.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Licencia eliminada']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
