<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\DashboardService;
use Exception;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    // GET /dashboard/summary
    #[Route('/dashboard/summary', 'GET')]
    public function summary()
    {
        try {
            $data = $this->dashboardService->getSummary();

            $this->json([
                "status" => "success",
                "data" => $data
            ]);
        } catch (Exception $e) {
            $this->json([
                "status" => "error",
                "message" => "Error al obtener el resumen del dashboard: " . $e->getMessage()
            ], 500);
        }
    }
}
