<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Product;
use PDO;

class ProductRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Product::class);
    }

    public function create(Product $product)
    {
        $stmt = $this->db->prepare("INSERT INTO products (category_id, sku, name, description, price, stock_status, image_url) VALUES (:category_id, :sku, :name, :description, :price, :stock_status, :image_url)");
        $stmt->execute([
            'category_id' => $product->category_id,
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock_status' => $product->stock_status ?? 'En Stock',
            'image_url' => $product->image_url
        ]);
        return $this->db->lastInsertId();
    }
}
