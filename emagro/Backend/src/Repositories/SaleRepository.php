<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Sale;
use PDO;

class SaleRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM sales");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Sale::class);
    }

    public function getSalesByCategory()
    {
        $stmt = $this->db->query("
            SELECT c.name as category, COUNT(sd.id) as items_sold 
            FROM sale_details sd
            JOIN products p ON sd.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            GROUP BY c.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalSalesAmount()
    {
        $stmt = $this->db->query("SELECT SUM(total_amount) as total FROM sales WHERE status = 'Completado'");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getRecentSales($limit = 5)
    {
        $stmt = $this->db->prepare("SELECT * FROM sales ORDER BY sale_date DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, Sale::class);
    }
}
