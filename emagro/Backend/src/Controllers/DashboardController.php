<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\DashboardService;
use App\Utils\Response;

class DashboardController extends Controller
{
    #[Route('/dashboard/stats', 'GET')]
    #[Authorize(['Administrador', 'Vendedor'])]
    public function index()
    {
        $service = new DashboardService();
        $stats = $service->getDashboardKPIs();
        Response::success($stats);
    }
}
