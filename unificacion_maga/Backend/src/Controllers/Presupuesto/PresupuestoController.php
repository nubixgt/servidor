<?php
namespace App\Controllers\Presupuesto;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\HasPrivilege;
use App\Services\Presupuesto\PresupuestoService;
use App\DTOs\Presupuesto\PresupuestoDTO;

#[HasPrivilege('modulo_presupuesto')]
class PresupuestoController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new PresupuestoService();
    }

    #[Route('/presupuesto/dashboard', 'GET')]
    public function dashboard()
    {
        try {
            $filters = $_GET;
            $data = $this->service->getDashboardData($filters);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/presupuesto/items', 'POST')]
    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = PresupuestoDTO::fromRequest($data);
            $id = $this->service->createRecord($dto->toArray());
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Nuevo Movimiento Presupuestario", "Se ha registrado un nuevo ítem en la ejecución del presupuesto.", "success"
            );
            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/presupuesto/items/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = PresupuestoDTO::fromRequest($data);
            $this->service->updateRecord($id, $dto->toArray());
            $this->json(['status' => 'success', 'message' => 'Presupuesto actualizado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/presupuesto/items/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->service->deleteRecord($id);
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Movimiento Presupuestario Eliminado", "Se ha eliminado un registro de ejecución presupuestaria.", "warning"
            );
            $this->json(['status' => 'success', 'message' => 'Presupuesto eliminado']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/presupuesto/import', 'POST')]
    public function import()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($data['datos'])) {
                throw new \Exception("No hay datos para importar");
            }
            
            $tipo = $data['tipo'] ?? 'PROGRAMA';
            $ejercicio = $data['ejercicio'] ?? 2026;
            $limpiarAntes = $data['limpiar_antes'] ?? true;
            
            $inserted = $this->service->importData($data['datos'], $tipo, $ejercicio, $limpiarAntes);
            
            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Importación de Presupuesto", "Se han importado $inserted registros para $tipo ($ejercicio).", "info"
            );
            
            $this->json([
                'success' => true,
                'status' => 'success', 
                'message' => "Se importaron $inserted registros correctamente."
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
