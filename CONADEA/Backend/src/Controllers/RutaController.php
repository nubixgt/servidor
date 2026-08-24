<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\GuardarRutaDTO;
use App\Services\RutaService;

class RutaController extends Controller
{
    #[Route('/rutas', 'GET')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function listar()
    {
        $service = new RutaService();
        $this->json(['status' => 'success', 'data' => $service->listar()]);
    }

    #[Route('/rutas/{id}', 'GET')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function obtener($id)
    {
        $service = new RutaService();
        try {
            $this->json(['status' => 'success', 'data' => $service->obtener((int) $id)]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    // body: {"icono", "titulo", "descripcion", "color", "curso_ids": [int, ...]}
    #[Route('/rutas', 'POST')]
    #[Authorize(['Administrador'])]
    public function crear()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = GuardarRutaDTO::fromRequest($data);
        $service = new RutaService();

        try {
            $ruta = $service->crear($dto);
            $this->json(['status' => 'success', 'message' => 'Ruta creada correctamente', 'data' => $ruta], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/rutas/{id}', 'PUT')]
    #[Authorize(['Administrador'])]
    public function actualizar($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = GuardarRutaDTO::fromRequest($data);
        $service = new RutaService();

        try {
            $ruta = $service->actualizar((int) $id, $dto);
            $this->json(['status' => 'success', 'message' => 'Ruta actualizada correctamente', 'data' => $ruta]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/rutas/{id}', 'DELETE')]
    #[Authorize(['Administrador'])]
    public function eliminar($id)
    {
        $service = new RutaService();
        try {
            $service->eliminar((int) $id);
            $this->json(['status' => 'success', 'message' => 'Ruta eliminada correctamente']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
