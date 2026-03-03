<?php
namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    private $productRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
    }

    public function getAllProducts()
    {
        return $this->productRepo->findAll();
    }

    public function getProductById($id)
    {
        $product = $this->productRepo->findById($id);
        if (!$product) {
            throw new \Exception("Producto no encontrado");
        }
        return $product;
    }

    public function createProduct($data)
    {
        if (empty($data['producto']) || empty($data['presentacion']) || !isset($data['precio'])) {
            throw new \Exception("Faltan campos obligatorios");
        }
        return $this->productRepo->create($data);
    }

    public function updateProduct($id, $data)
    {
        $this->getProductById($id); // verify existence

        if (empty($data['producto']) || empty($data['presentacion']) || !isset($data['precio'])) {
            throw new \Exception("Faltan campos obligatorios");
        }

        return $this->productRepo->update($id, $data);
    }

    public function deleteProduct($id)
    {
        $this->getProductById($id); // verify existence
        return $this->productRepo->delete($id);
    }
}
