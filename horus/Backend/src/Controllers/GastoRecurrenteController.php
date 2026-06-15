<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\GastoRecurrenteService;
use App\DTOs\GastoRecurrenteDTO;

class GastoRecurrenteController extends Controller {

    #[Route('/gastos-recurrentes', 'GET')]
    public function getAll() {
        try {
            $service = new GastoRecurrenteService();
            $data = $service->getAllGastos();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/gastos-recurrentes/:id', 'GET')]
    public function getById($id) {
        try {
            $service = new GastoRecurrenteService();
            $data = $service->getGastoById($id);
            if (!$data) {
                $this->json(['status' => 'error', 'message' => 'Gasto no encontrado'], 404);
                return;
            }
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/gastos-recurrentes', 'POST')]
    public function create() {
        try {
            $input = $this->getJsonInput();
            $dto = new GastoRecurrenteDTO($input);
            $service = new GastoRecurrenteService();
            $data = $service->createGasto($dto);
            $this->json(['status' => 'success', 'data' => $data, 'message' => 'Gasto recurrente creado con éxito'], 201);
        } catch (\InvalidArgumentException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/gastos-recurrentes/:id', 'PUT')]
    public function update($id) {
        try {
            $input = $this->getJsonInput();
            $dto = new GastoRecurrenteDTO($input);
            $service = new GastoRecurrenteService();
            $data = $service->updateGasto($id, $dto);
            $this->json(['status' => 'success', 'data' => $data, 'message' => 'Gasto recurrente actualizado con éxito']);
        } catch (\InvalidArgumentException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $code = $e->getMessage() === 'Gasto recurrente no encontrado.' ? 404 : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/gastos-recurrentes/:id', 'DELETE')]
    public function delete($id) {
        try {
            $service = new GastoRecurrenteService();
            $service->deleteGasto($id);
            $this->json(['status' => 'success', 'message' => 'Gasto recurrente eliminado con éxito']);
        } catch (\Exception $e) {
            $code = $e->getMessage() === 'Gasto recurrente no encontrado.' ? 404 : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}

