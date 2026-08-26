<?php
namespace App\Controllers\Extension;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Extension\ExtensionService;
use App\DTOs\Extension\VisitaDTO;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_extension')]
class ExtensionController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new ExtensionService();
    }

    #[Route('/extension', 'GET')]
    public function index()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getVisitas($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/extension/extensionistas', 'GET')]
    public function extensionistas()
    {
        try {
            $data = $this->service->getExtensionistas();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/extension', 'POST')]
    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = VisitaDTO::fromRequest($data);
            $id = $this->service->createVisita($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Visita de Extensión", "Se ha registrado una nueva visita de extensión rural.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/extension/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = VisitaDTO::fromRequest($data);
            $this->service->updateVisita($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Visita actualizada correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/extension/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->service->deleteVisita($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Visita de Extensión Eliminada", "Se ha eliminado un registro de visita de extensión rural.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Visita eliminada correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
