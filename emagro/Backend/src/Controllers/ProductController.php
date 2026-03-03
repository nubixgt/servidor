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
    #[Authorize(['admin', 'vendedor'])]
    public function index()
    {
        $service = new ProductService();
        $products = $service->getAllProducts();
        Response::success($products);
    }

    #[Route('/products', 'POST')]
    #[Authorize(['admin'])]
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $service = new ProductService();
        try {
            $id = $service->createProduct($data);
            Response::success(['id' => $id, 'message' => 'Producto creado exitosamente'], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/products/{id}', 'PUT')]
    #[Authorize(['admin'])]
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $service = new ProductService();
        try {
            $service->updateProduct($id, $data);
            Response::success(['message' => 'Producto actualizado exitosamente']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    #[Route('/products/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    public function delete($id)
    {
        $service = new ProductService();
        try {
            $service->deleteProduct($id);
            Response::success(['message' => 'Producto eliminado correctamente']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
