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
    #[Authorize(['admin', 'vendedor'])]
    public function index()
    {
        $service = new SaleService();
        $sales = $service->getAllSales();
        Response::success($sales);
    }

    #[Route('/sales/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function delete($id)
    {
        $service = new SaleService();
        try {
            $service->deleteSale($id);
            Response::success(['message' => 'Venta eliminada correctamente']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    #[Route('/sales/{id}', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    public function show($id)
    {
        $service = new SaleService();
        try {
            $sale = $service->getSaleWithDetails($id);
            if (!$sale) {
                Response::error('Venta no encontrada', 404);
            }
            Response::success($sale);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    #[Route('/sales', 'POST')]
    #[Authorize(['admin', 'vendedor'])]
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            Response::error('Datos inválidos', 400);
        }

        $service = new SaleService();

        try {
            // we will need the user_id for the nota_envio table
            // assuming it's injected if Auth middleware gave it, or we rely on a passed param/session
            if (!isset($data['usuario_id'])) {
                $data['usuario_id'] = 1; // Fallback or retrieve from auth
            }

            $result = $service->createSale($data);
            Response::success($result, 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
