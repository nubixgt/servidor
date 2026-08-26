<?php
namespace App\Controllers\Productores;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Productores\ProductorService;
use App\DTOs\Productores\ProductorDTO;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_productores')]
class ProductorController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new ProductorService();
    }

    #[Route('/productores', 'GET')]
    public function index()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getProductores($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/productores/{id}', 'GET')]
    public function show($id)
    {
        try {
            $data = $this->service->getProductor($id);
            if (!$data) {
                $this->json(['status' => 'error', 'message' => 'Productor no encontrado'], 404);
                return;
            }
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/productores', 'POST')]
    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = ProductorDTO::fromRequest($data);
            $id = $this->service->createProductor($dto->toArray());
            
            // Disparar Notificación Global Inteligente
            $notifService = new \App\Services\Notifications\NotificationService();
            $notifService->createGlobalNotification(
                "Nuevo Productor Registrado", 
                "Se ha ingresado exitosamente a " . ($data['nombre_completo'] ?? 'un nuevo productor') . " en el Padrón Nacional.",
                "success"
            );

            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/productores/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = ProductorDTO::fromRequest($data);
            $this->service->updateProductor($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Productor actualizado correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/productores/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->service->deleteProductor($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Productor Eliminado", "Se ha eliminado un registro del Padrón de Productores.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Productor eliminado correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
