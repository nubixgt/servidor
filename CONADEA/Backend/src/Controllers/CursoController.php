<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\CrearCursoDTO;
use App\Services\CursoService;

class CursoController extends Controller
{
    #[Route('/cursos', 'POST')]
    #[Authorize(['Administrador'])]
    public function crear()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = CrearCursoDTO::fromRequest($data);
        $service = new CursoService();

        try {
            $curso = $service->crear($dto);
            $this->json(['status' => 'success', 'message' => 'Curso creado correctamente', 'data' => $curso], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/cursos', 'GET')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function listar()
    {
        $service = new CursoService();
        $this->json(['status' => 'success', 'data' => $service->listar()]);
    }

    #[Route('/cursos/{id}', 'GET')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function obtener($id)
    {
        $service = new CursoService();
        try {
            $this->json(['status' => 'success', 'data' => $service->obtener((int) $id)]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
}
