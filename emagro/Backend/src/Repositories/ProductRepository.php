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
        $stmt = $this->db->query("SELECT * FROM productos_precios");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Product::class);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES (:producto, :presentacion, :precio, :cantidad)");
        $stmt->execute([
            ':producto' => $data['producto'],
            ':presentacion' => $data['presentacion'],
            ':precio' => $data['precio'],
            ':cantidad' => $data['cantidad'] ?? 0
        ]);
        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM productos_precios WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Product::class);
        return $stmt->fetch();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE productos_precios 
            SET producto = :producto, presentacion = :presentacion, precio = :precio, cantidad = :cantidad 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':producto' => $data['producto'],
            ':presentacion' => $data['presentacion'],
            ':precio' => $data['precio'],
            ':cantidad' => $data['cantidad'] ?? 0
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM productos_precios WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
