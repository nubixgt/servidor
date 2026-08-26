<?php
namespace App\Controllers\ActividadesDespacho;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\ActividadesDespacho\ActividadService;
use App\DTOs\ActividadesDespacho\ActividadDTO;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_despacho')]
class ActividadController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new ActividadService();
    }

    #[Route('/actividades-despacho', 'GET')]
    public function index()
    {
        try {
            $data = $this->service->getActividades();
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/actividades-despacho', 'POST')]
    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = ActividadDTO::fromRequest($data);
            
            $id = $this->service->createActividad($dto->toArray());
            
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nueva Actividad del Despacho", "Se ha registrado una nueva actividad ministerial.", "success"
            );

            $this->json([
                'status' => 'success',
                'message' => 'Actividad creada correctamente',
                'id' => $id
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/actividades-despacho/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = ActividadDTO::fromRequest($data);
            
            $this->service->updateActividad($id, $dto->toArray());
            
            $this->json([
                'status' => 'success',
                'message' => 'Actividad actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/actividades-despacho/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->service->deleteActividad($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Actividad Eliminada", "Se ha eliminado un registro de actividad del Despacho.", "warning"
            );
            $this->json([
                'status' => 'success',
                'message' => 'Actividad eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/actividades-despacho/tecnicos', 'GET')]
    public function tecnicos()
    {
        try {
            $data = $this->service->getTecnicos();
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/actividades-despacho/tecnicos', 'POST')]
    public function createTecnico()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = $this->service->createTecnico($data);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Técnico Asignado", "Se ha registrado un nuevo técnico para el Despacho Superior.", "info"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/actividades-despacho/tecnicos/{id}', 'PUT')]
    public function updateTecnico($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $this->service->updateTecnico($id, $data);
            $this->json(['status' => 'success', 'message' => 'Técnico actualizado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/actividades-despacho/tecnicos/{id}', 'DELETE')]
    public function deleteTecnico($id)
    {
        try {
            $this->service->deleteTecnico($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Técnico Desactivado", "Se ha desactivado un técnico del Despacho Superior.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Técnico desactivado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al desactivar técnico'], 400);
        }
    }

    #[Route('/actividades-despacho/stats', 'GET')]
    public function stats()
    {
        try {
            $data = $this->service->getStats();
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
