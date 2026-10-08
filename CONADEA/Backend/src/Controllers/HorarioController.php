<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\HorarioService;
use App\Utils\AuthContext;

class HorarioController extends Controller
{
    // Horarios de estudio del usuario autenticado (alimenta el calendario).
    #[Route('/horarios', 'GET')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function listar()
    {
        $service = new HorarioService();
        $this->json(['status' => 'success', 'data' => $service->listar(AuthContext::usuarioId())]);
    }

    // body: curso_id, dias ("L,M,X,V"), hora ("07:00"), duracion_minutos
    #[Route('/horarios', 'POST')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function guardar()
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new HorarioService();

        try {
            $service->guardar(
                AuthContext::usuarioId(),
                (int) ($body['curso_id'] ?? 0),
                (string) ($body['dias'] ?? ''),
                (string) ($body['hora'] ?? ''),
                (int) ($body['duracion_minutos'] ?? 15)
            );
            $this->json(['status' => 'success']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    // body: curso_id, activo (bool) — pausa/reactiva los avisos de ese horario
    #[Route('/horarios/activo', 'POST')]
    #[Authorize(['Administrador', 'Supervisor', 'Usuario'])]
    public function actualizarActivo()
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new HorarioService();
        $ok = $service->actualizarActivo(
            AuthContext::usuarioId(),
            (int) ($body['curso_id'] ?? 0),
            (bool) ($body['activo'] ?? true)
        );

        if (!$ok) {
            $this->json(['status' => 'error', 'message' => 'No hay un horario configurado para ese curso'], 404);
        }
        $this->json(['status' => 'success']);
    }
}
