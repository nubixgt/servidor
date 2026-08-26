<?php
namespace App\Controllers\Clima;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Clima\AlertaService;
use App\DTOs\Clima\AlertaDTO;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_clima')]
class AlertaController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new AlertaService();
    }

    #[Route('/clima/alertas', 'GET')]
    public function index()
    {
        try {
            $data = $this->service->getAlertas();
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/clima/alertas/{id}', 'GET')]
    public function show($id)
    {
        try {
            $alerta = $this->service->getAlerta($id);
            if (!$alerta) {
                return $this->json(['status' => 'error', 'message' => 'Alerta no encontrada'], 404);
            }
            $this->json(['status' => 'success', 'data' => $alerta]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/alertas', 'POST')]
    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            // Inyectar el usuario autenticado (simulado para este ejemplo, debería venir del auth token)
            // $data['idUsuarioCreador'] = Auth::user()->id; 
            
            $dto = AlertaDTO::fromRequest($data);
            $id = $this->service->createAlerta($dto);
            
            $this->json([
                'status' => 'success',
                'message' => 'Alerta creada correctamente',
                'data' => ['id' => $id]
            ], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/alertas/{id}', 'PUT')]
    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = AlertaDTO::fromRequest($data);
            $this->service->updateAlerta($id, $dto);
            
            $this->json([
                'status' => 'success',
                'message' => 'Alerta actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/alertas/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $this->service->deleteAlerta($id);
            $this->json([
                'status' => 'success',
                'message' => 'Alerta eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
