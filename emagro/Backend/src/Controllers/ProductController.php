<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\ProductService;
use App\Utils\Response;

class ProductController extends Controller
{
    #[Route('/products', 'GET')]
    #[Authorize(['Administrador', 'Vendedor'])]
    public function index()
    {
        $service = new ProductService();
        $products = $service->getAllProducts();
        Response::success($products);
    }
}
