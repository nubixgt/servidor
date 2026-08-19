<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\DashboardService;
use App\Services\AuthService;

class DashboardController extends Controller
{
    #[Route('/dashboard/resumen', 'GET')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function resumen()
    {
        $currentUser = AuthService::currentPayload();
        $service = new DashboardService();
        $this->json($service->resumen($currentUser));
    }
}
