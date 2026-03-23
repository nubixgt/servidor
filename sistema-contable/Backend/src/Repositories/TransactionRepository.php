<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class TransactionRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $query = "
            INSERT INTO financial_transactions (
                type, amount, transaction_date, location_id, category, provider, description, receipt_path, status, created_by
            ) VALUES (
                :type, :amount, :transaction_date, :location_id, :category, :provider, :description, :receipt_path, 'Aprobado', :created_by
            )
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transaction_date'],
            'location_id' => $data['location_id'],
            'category' => $data['category'],
            'provider' => $data['provider'] ?? null,
            'description' => $data['description'],
            'receipt_path' => $data['receipt_path'] ?? null,
            'created_by' => $data['created_by']
        ]);

        return $this->db->lastInsertId();
    }

    public function getKPIs()
    {
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN type = 'ingreso' AND status = 'Aprobado' THEN amount ELSE 0 END) as total_ingresos,
                SUM(CASE WHEN type = 'egreso' THEN amount ELSE 0 END) as total_egresos
            FROM financial_transactions
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findRecent($limit = 5)
    {
        $query = "
            SELECT t.*, l.name as location_name, u.name as user_name 
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            ORDER BY t.created_at DESC LIMIT :limit
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findAll()
    {
        $query = "
            SELECT t.*, l.name as location_name, u.name as user_name 
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            ORDER BY t.transaction_date DESC, t.created_at DESC
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
