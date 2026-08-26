<?php
namespace App\Controllers\Dashboard;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    #[Route('/dashboard/stats', 'GET')]
    public function stats()
    {
        try {
            $data = $this->service->getGlobalStats();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    }
}
