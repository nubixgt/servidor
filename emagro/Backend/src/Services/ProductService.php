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
}
