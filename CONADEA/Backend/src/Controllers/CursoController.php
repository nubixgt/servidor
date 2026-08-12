<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\CrearCursoDTO;
use App\Services\CursoService;

class CursoController extends Controller
{
    // multipart/form-data: campo "data" con el JSON del curso + campo
    // "imagen" con el archivo + un campo "leccion_video_N" por cada lección
    // (N = su posición en el array "lecciones" de "data") que tenga video
    // adjunto — el video es opcional, así que no todas las lecciones lo traen.
    #[Route('/cursos', 'POST')]
    #[Authorize(['Administrador'])]
    public function crear()
    {
        $data = json_decode($_POST['data'] ?? '{}', true) ?? [];
        $dto = CrearCursoDTO::fromRequest($data);
        $service = new CursoService();

        $archivosVideo = [];
        foreach ($_FILES as $campo => $archivo) {
            if (preg_match('/^leccion_video_(\d+)$/', $campo, $matches)
                && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $archivosVideo[(int) $matches[1]] = $archivo;
            }
        }

        try {
            $curso = $service->crear($dto, $_FILES['imagen'] ?? null, $archivosVideo);
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
