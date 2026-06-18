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

        $m3_arena   = isset($data['m3_arena'])   ? (float)$data['m3_arena']   : 0;
        $m3_piedrin = isset($data['m3_piedrin']) ? (float)$data['m3_piedrin'] : 0;
        $m3_cemento = isset($data['m3_cemento']) ? (float)$data['m3_cemento'] : 0;

        if (!$data || ($m3_arena <= 0 && $m3_piedrin <= 0 && $m3_cemento <= 0)) {
            $this->json(['status' => 'error', 'message' => 'Debe ingresar al menos una cantidad mayor a 0.'], 400);
            return;
        }

        // Validate stock for each requested product
        $productos = [
            'Arena'   => $m3_arena,
            'Piedrin' => $m3_piedrin,
            'Cemento' => $m3_cemento,
        ];

        $itemsToDeduct = [];
        foreach ($productos as $nombre => $cantidad) {
            if ($cantidad <= 0) continue;
            $item = $this->inventoryRepo->findByNombre($nombre);
            if (!$item) {
                $this->json(['status' => 'error', 'message' => "El producto {$nombre} no existe en el inventario."], 404);
                return;
            }
            if ($item['stock_actual'] < $cantidad) {
                $this->json(['status' => 'error', 'message' => "Stock insuficiente de {$nombre}. Disponible: {$item['stock_actual']}"], 400);
                return;
            }
            $itemsToDeduct[] = ['id' => $item['id'], 'cantidad' => $cantidad];
        }

        try {
            foreach ($itemsToDeduct as $entry) {
                $this->inventoryRepo->updateStock($entry['id'], $entry['cantidad'], '-');
            }

            $user = $this->getUser();
            $data['created_by'] = $user ? $user['id'] : null;

            $tripId = $this->tripRepo->create($data);

            $this->json(['status' => 'success', 'message' => 'Viaje creado exitosamente', 'data' => ['id' => $tripId]]);
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/concrete/trips/{id}/pilot', 'PUT')]
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

    #[Route('/concrete/trips/{id}/placement', 'PUT')]
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
