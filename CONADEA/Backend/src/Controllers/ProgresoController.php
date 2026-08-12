<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\ProgresoService;
use App\Utils\AuthContext;

class ProgresoController extends Controller
{
    // Todo el progreso (lecciones + cursos) del usuario autenticado — se
    // pide una sola vez al entrar a la app para poblar ProgresoController
    // en el cliente (antes vivía solo en memoria y no sobrevivía a cerrar
    // la app).
    #[Route('/progreso', 'GET')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function listar()
    {
        $usuarioId = AuthContext::usuarioId();
        $service = new ProgresoService();
        $this->json(['status' => 'success', 'data' => $service->obtenerTodo($usuarioId)]);
    }

    // body: {"completada": bool|null, "segundos_video": int|null} — ambos
    // opcionales, para poder guardar solo el que cambió (marcar completada,
    // o el checkpoint periódico de reproducción del video).
    #[Route('/lecciones/{id}/progreso', 'PUT')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function guardarLeccion($id)
    {
        $usuarioId = AuthContext::usuarioId();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new ProgresoService();

        try {
            $resultado = $service->guardarLeccion(
                $usuarioId,
                (int) $id,
                array_key_exists('completada', $data) ? (bool) $data['completada'] : null,
                array_key_exists('segundos_video', $data) ? (int) $data['segundos_video'] : null
            );
            $this->json(['status' => 'success', 'data' => $resultado]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // body: {"nota": int, "total": int}
    #[Route('/cursos/{id}/evaluacion', 'PUT')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function guardarEvaluacion($id)
    {
        $usuarioId = AuthContext::usuarioId();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new ProgresoService();

        try {
            $resultado = $service->guardarEvaluacion(
                $usuarioId,
                (int) $id,
                (int) ($data['nota'] ?? 0),
                (int) ($data['total'] ?? 0)
            );
            $this->json(['status' => 'success', 'data' => $resultado]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
