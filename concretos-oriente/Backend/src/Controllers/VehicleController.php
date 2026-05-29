<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\VehicleService;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Utils\Response;
use Exception;

class VehicleController extends Controller
{
    private VehicleService $vehicleService;

    public function __construct()
    {
        $this->vehicleService = new VehicleService();
    }

    #[Route('/vehicles', 'GET')]
    #[Authorize]
    public function index(): void
    {
        try {
            $vehicles = $this->vehicleService->getAllVehicles();
            Response::json(['success' => true, 'data' => $vehicles]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/vehicles', 'POST')]
    #[Authorize]
    public function store(): void
    {
        try {
            $data = $_POST;
            $files = $_FILES;

            $result = $this->vehicleService->createVehicle($data, $files);
            Response::json($result, 201);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/vehicles/update/{id}', 'POST')]
    #[Authorize]
    public function update(int $id): void
    {
        try {
            // Se usa POST para la actualización porque enviaremos multipart/form-data
            $data = $_POST;
            $files = $_FILES;

            $result = $this->vehicleService->updateVehicle($id, $data, $files);
            Response::json($result);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/vehicles/{id}', 'DELETE')]
    #[Authorize]
    public function destroy(int $id): void
    {
        try {
            $result = $this->vehicleService->deleteVehicle($id);
            Response::json($result);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
