<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\PagoDTO;
use App\Services\PagoService;

class PagoController extends Controller
{
    #[Route('/pagos', 'GET')]
    public function index()
    {
        $service = new PagoService();
        $data = $service->getAllPagos();

        $this->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    #[Route('/pagos/{id}', 'GET')]
    public function show(int $id)
    {
        $service = new PagoService();
        $pago = $service->getPagoById($id);

        if (!$pago) {
            $this->json(['error' => 'Pago no encontrado'], 404);
            return;
        }

        $this->json([
            'status' => 'success',
            'data' => $pago
        ]);
    }

    #[Route('/pagos', 'POST')]
    public function create()
    {
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) ?? [] : $_POST;
        $dto = PagoDTO::fromRequest($data);
        $service = new PagoService();

        try {
            $pago = $service->createPago($dto);
            $this->json([
                'status' => 'success',
                'message' => 'Pago registrado exitosamente',
                'data' => $pago
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/pagos/{id}', 'POST')]
    public function update(int $id)
    {
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) ?? [] : $_POST;
        $dto = PagoDTO::fromRequest($data);
        $service = new PagoService();

        try {
            $pago = $service->updatePago($id, $dto);
            $this->json([
                'status' => 'success',
                'message' => 'Pago actualizado exitosamente',
                'data' => $pago
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/pagos/{id}', 'DELETE')]
    public function delete(int $id)
    {
        $service = new PagoService();

        try {
            $service->deletePago($id);
            $this->json([
                'status' => 'success',
                'message' => 'Pago eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
