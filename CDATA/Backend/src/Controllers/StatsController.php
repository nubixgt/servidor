<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\StatsService;

class StatsController extends Controller
{
    #[Route('/stats', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function getStats()
    {
        $service = new StatsService();
        
        try {
            $data = $service->getDashboardStats();
            $this->json($data);
        } catch (\Exception $e) {
            $this->json(['error' => 'Error al obtener estadísticas', 'details' => $e->getMessage()], 500);
        }
    }
}
