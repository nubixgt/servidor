<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    #[Route('/dashboard/resumen', 'GET')]
    #[Authorize(['admin'])]
    public function resumen()
    {
        $this->run(fn () => (new DashboardService())->resumen());
    }
}
