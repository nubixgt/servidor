<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class LocationRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        // Join with users to get the responsible name if available
        $query = "
            SELECT l.*, u.name as responsible_name 
            FROM locations l
            LEFT JOIN users u ON l.responsible_id = u.id
            ORDER BY l.id ASC
        ";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
