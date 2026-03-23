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
            SELECT l.*, COALESCE(l.responsible_name, u.name) as responsible_name 
            FROM locations l
            LEFT JOIN users u ON l.responsible_id = u.id
            ORDER BY l.id ASC
        ";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $query = "INSERT INTO locations (code, type, photo_path, name, address, status, responsible_name) 
                  VALUES (:code, :type, :photo_path, :name, :address, :status, :responsible_name)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'code' => $data['code'] ?? 'L-' . round(microtime(true) * 1000),
            'type' => $data['type'],
            'photo_path' => $data['photo_path'] ?? null,
            'name' => $data['name'],
            'address' => $data['address'],
            'status' => 'Activa',
            'responsible_name' => $data['responsible_name'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM locations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, array $data)
    {
        $query = "UPDATE locations SET 
                    type = :type, 
                    name = :name, 
                    address = :address, 
                    responsible_name = :responsible_name";
                    
        $params = [
            'id' => $id,
            'type' => $data['type'],
            'name' => $data['name'],
            'address' => $data['address'],
            'responsible_name' => $data['responsible_name'] ?? null
        ];

        if (isset($data['photo_path']) && $data['photo_path'] !== '') {
            $query .= ", photo_path = :photo_path";
            $params['photo_path'] = $data['photo_path'];
        }

        $query .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM locations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
