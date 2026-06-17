<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\ConcreteTripRepository;
use App\Repositories\InventoryItemRepository;
use Exception;

class ConcreteControlController extends Controller
{
    private ConcreteTripRepository $tripRepo;
    private InventoryItemRepository $inventoryRepo;

    public function __construct()
    {
        $this->tripRepo = new ConcreteTripRepository();
        $this->inventoryRepo = new InventoryItemRepository();
    }

    #[Route('/concrete/trips', 'GET')]
    public function getTrips(): void
    {
        try {
            $trips = $this->tripRepo->findAll();
            $this->json(['status' => 'success', 'data' => $trips]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/concrete/trips', 'POST')]
    public function createTrip(): void
    {
        $data = $this->getJsonInput();
        if (!$data || !isset($data['producto']) || !isset($data['m3'])) {
            $this->json(['status' => 'error', 'message' => 'Faltan datos requeridos (producto, m3).'], 400);
            return;
        }

        $item = $this->inventoryRepo->findByNombre($data['producto']);
        if (!$item) {
            $this->json(['status' => 'error', 'message' => 'El producto ' . $data['producto'] . ' no existe en el inventario.'], 404);
            return;
        }

        if ($item['stock_actual'] < $data['m3']) {
            $this->json(['status' => 'error', 'message' => 'Stock insuficiente de ' . $data['producto'] . '. Disponible: ' . $item['stock_actual']], 400);
            return;
        }

        try {
            $this->inventoryRepo->updateStock($item['id'], $data['m3'], '-');
            
            // Get user ID from JWT token
            $user = $this->getUser();
            $data['created_by'] = $user ? $user['id'] : null;

            $tripId = $this->tripRepo->create($data);
            
            $this->json(['status' => 'success', 'message' => 'Viaje creado exitosamente', 'data' => ['id' => $tripId]]);
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/concrete/trips/:id/pilot', 'PUT')]
    public function updatePilot(string $id): void
    {
        $data = $this->getJsonInput();
        if (!$data || !isset($data['hora_salida']) || !isset($data['hora_llegada'])) {
            $this->json(['status' => 'error', 'message' => 'Faltan datos de tiempo.'], 400);
            return;
        }

        try {
            $this->tripRepo->updatePilotStep((int)$id, $data);
            $this->json(['status' => 'success', 'message' => 'Actualizado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/concrete/trips/:id/placement', 'PUT')]
    public function updatePlacement(string $id): void
    {
        $data = $this->getJsonInput();
        if (!$data || !isset($data['tipo_concreto']) || !isset($data['hora_llegada_obra'])) {
            $this->json(['status' => 'error', 'message' => 'Faltan datos de colocación.'], 400);
            return;
        }

        try {
            $this->tripRepo->updatePlacementStep((int)$id, $data);
            $this->json(['status' => 'success', 'message' => 'Colocación registrada correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
