<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\PrestamoDTO;
use App\Services\PrestamoService;

class PrestamoController extends Controller
{
    #[Route('/prestamos', 'GET')]
    public function index()
    {
        $service = new PrestamoService();
        try {
            $prestamos = $service->getAll();
            $this->json([
                'status' => 'success',
                'data' => $prestamos
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/prestamos/{id}', 'GET')]
    public function show(int $id)
    {
        $service = new PrestamoService();
        try {
            $prestamo = $service->getById($id);
            if ($prestamo) {
                $this->json([
                    'status' => 'success',
                    'data' => $prestamo
                ]);
            } else {
                $this->json(['error' => 'Préstamo no encontrado'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/prestamos', 'POST')]
    public function create()
    {
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) ?? [] : $_POST;
        $dto = PrestamoDTO::fromRequest($data);
        $service = new PrestamoService();

        try {
            $prestamo = $service->create($dto);
            $this->json([
                'status' => 'success',
                'message' => 'Préstamo creado exitosamente',
                'data' => $prestamo
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/prestamos/{id}', 'POST')]
    public function update(int $id)
    {
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) ?? [] : $_POST;
        $dto = PrestamoDTO::fromRequest($data);
        $service = new PrestamoService();

        try {
            $prestamo = $service->update($id, $dto);
            $this->json([
                'status' => 'success',
                'message' => 'Préstamo actualizado exitosamente',
                'data' => $prestamo
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/prestamos/{id}', 'DELETE')]
    public function delete(int $id)
    {
        $service = new PrestamoService();

        try {
            $success = $service->delete($id);
            if ($success) {
                $this->json([
                    'status' => 'success',
                    'message' => 'Préstamo eliminado exitosamente'
                ]);
            } else {
                $this->json(['error' => 'Préstamo no encontrado'], 404);
            }
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
