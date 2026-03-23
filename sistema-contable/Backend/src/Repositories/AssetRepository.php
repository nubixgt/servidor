<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class AssetRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findAllAssets()
    {
        $stmt = $this->db->query("SELECT * FROM assets ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTransaction($data)
    {
        $query = "
            INSERT INTO asset_transactions (
                type, asset_id, location_id, status, created_by
            ) VALUES (
                :type, :asset_id, :location_id, 'Completado', :created_by
            )
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'type' => $data['type'],
            'asset_id' => $data['asset_id'],
            'location_id' => $data['location_id'],
            'created_by' => $data['created_by']
        ]);

        // Update the asset's current location to match the transaction
        $updateStmt = $this->db->prepare("UPDATE assets SET current_location_id = :loc WHERE id = :id");
        $updateStmt->execute([
            'loc' => $data['location_id'],
            'id' => $data['asset_id']
        ]);

        return $this->db->lastInsertId();
    }

    public function findRecentActivity($limit = 5)
    {
        $query = "
            SELECT t.*, a.name as asset_name, l.name as location_name, u.name as user_name 
            FROM asset_transactions t
            JOIN assets a ON t.asset_id = a.id
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            ORDER BY t.created_at DESC LIMIT :limit
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
