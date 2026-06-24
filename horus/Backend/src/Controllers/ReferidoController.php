<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\ReferidoDTO;
use App\Services\ReferidoService;

class ReferidoController extends Controller
{
    #[Route('/referidos', 'GET')]
    public function index()
    {
        $service = new ReferidoService();
        $this->json([
            'status' => 'success',
            'data' => $service->getAll()
        ]);
    }

    #[Route('/referidos/{id}', 'GET')]
    public function show(int $id)
    {
        $service = new ReferidoService();
        $referido = $service->getById($id);
        
        if (!$referido) {
            $this->json(['status' => 'error', 'message' => 'Not found'], 404);
            return;
        }

        $this->json([
            'status' => 'success',
            'data' => $referido
        ]);
    }

    #[Route('/referidos', 'POST')]
    public function create()
    {
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) ?? [] : $_POST;
        
        $dto = ReferidoDTO::fromRequest($data);
        $service = new ReferidoService();

        try {
            $referido = $service->create($dto);
            $this->json([
                'status' => 'success',
                'message' => 'Referido creado exitosamente',
                'data' => $referido
            ], 201);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/referidos/{id}', 'POST')]
    public function update(int $id)
    {
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $data = $isJson ? json_decode(file_get_contents('php://input'), true) ?? [] : $_POST;
        
        $dto = ReferidoDTO::fromRequest($data);
        $service = new ReferidoService();

        try {
            $referido = $service->update($id, $dto);
            $this->json([
                'status' => 'success',
                'message' => 'Referido actualizado exitosamente',
                'data' => $referido
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/referidos/{id}', 'DELETE')]
    public function delete(int $id)
    {
        $service = new ReferidoService();
        $success = $service->delete($id);

        if ($success) {
            $this->json([
                'status' => 'success',
                'message' => 'Referido eliminado'
            ]);
        } else {
            $this->json([
                'status' => 'error',
                'message' => 'No se pudo eliminar'
            ], 400);
        }
    }

    #[Route('/referidos/([0-9]+)/pagos', 'GET')]
    public function getPagos($id)
    {
        $service = new ReferidoService();
        try {
            $data = $service->getHistorialPagos($id);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/referidos/([0-9]+)/pagos', 'POST')]
    public function addPago($id)
    {
        $service = new ReferidoService();
        try {
            $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
            $data = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;

            $pagoId = $service->addPago($id, $data);
            $this->json(['status' => 'success', 'data' => ['id' => $pagoId]], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 400);
        }
    }

    #[Route('/referidos/pagos/([0-9]+)', 'DELETE')]
    public function deletePago($id)
    {
        $service = new ReferidoService();
        try {
            $success = $service->deletePago($id);
            if ($success) {
                $this->json(['status' => 'success', 'message' => 'Pago eliminado']);
            } else {
                $this->json(['status' => 'error', 'message' => 'No se pudo eliminar'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }
}
