<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\CalificacionService;
use App\Services\LeaderboardService;
use App\Utils\Response;

class CalificacionController extends Controller
{
    #[Route('/calificaciones/{ronda}', 'POST')]
    #[Authorize(['admin', 'jurado'])]
    public function guardar($ronda)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $participanteId = (int) ($data['participante_id'] ?? 0);
        $juradoId = Auth::id();

        $service = new CalificacionService();

        try {
            $result = $service->guardar($ronda, $participanteId, $juradoId, $data);
            Response::success($result, 'Calificación guardada');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/calificaciones/{ronda}/mias', 'GET')]
    #[Authorize(['admin', 'jurado'])]
    public function mias($ronda)
    {
        $juradoId = Auth::id();
        $service = new CalificacionService();

        try {
            Response::success($service->misCalificaciones($ronda, $juradoId));
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/leaderboard', 'GET')]
    #[Authorize(['admin'])]
    public function leaderboard()
    {
        $service = new LeaderboardService();
        Response::success($service->calcular());
    }
}
