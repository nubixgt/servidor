<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\RecurrentService;
use Exception;

class RecurrentController extends Controller
{
    private RecurrentService $recurrentService;

    public function __construct()
    {
        $this->recurrentService = new RecurrentService();
    }

    #[Route('/recurrents', 'GET')]
    public function index()
    {
        try {
            $user = $this->getUser();
            $data = $this->recurrentService->getAllByUser($user['id']);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/recurrents', 'POST')]
    public function store()
    {
        try {
            $user = $this->getUser();
            
            $input = json_decode(file_get_contents('php://input'), true);
            $data = [
                'concepto'    => trim($input['concepto'] ?? ''),
                'descripcion' => trim($input['descripcion'] ?? '') ?: null,
                'monto'       => isset($input['monto']) && $input['monto'] !== '' ? (float)$input['monto'] : null,
                'dia_pago'    => isset($input['dia_pago']) && $input['dia_pago'] !== '' ? (int)$input['dia_pago'] : null,
            ];

            $this->recurrentService->create($data, $user['id']);
            $this->json(['status' => 'success', 'message' => 'Recurrente creado exitosamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/recurrents/(\d+)', 'PUT')]
    public function update(int $id)
    {
        try {
            $user = $this->getUser();
            
            $input = json_decode(file_get_contents('php://input'), true);
            $data = [
                'concepto'    => trim($input['concepto'] ?? ''),
                'descripcion' => trim($input['descripcion'] ?? '') ?: null,
                'monto'       => isset($input['monto']) && $input['monto'] !== '' ? (float)$input['monto'] : null,
                'dia_pago'    => isset($input['dia_pago']) && $input['dia_pago'] !== '' ? (int)$input['dia_pago'] : null,
            ];

            $this->recurrentService->update($id, $data, $user['id']);
            $this->json(['status' => 'success', 'message' => 'Recurrente actualizado exitosamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/recurrents/(\d+)', 'DELETE')]
    public function destroy(int $id)
    {
        try {
            $user = $this->getUser();
            $this->recurrentService->delete($id, $user['id']);
            $this->json(['status' => 'success', 'message' => 'Recurrente eliminado exitosamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
