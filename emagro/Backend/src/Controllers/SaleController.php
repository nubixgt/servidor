<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\SaleService;
use App\Utils\Response;

class SaleController extends Controller
{
    #[Route('/sales', 'GET')]
    #[Authorize(['Administrador', 'Vendedor'])]
    public function index()
    {
        $service = new SaleService();
        $sales = $service->getAllSales();
        Response::success($sales);
    }
}
