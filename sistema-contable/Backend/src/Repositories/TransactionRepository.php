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
                :type, :amount, :transaction_date, :location_id, :category, :provider, :description, :receipt_path, 'Pendiente', :created_by
            )
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'type'             => $data['type'],
            'amount'           => $data['amount'],
            'transaction_date' => $data['transaction_date'],
            'location_id'      => $data['location_id'],
            'category'         => $data['category'],
            'provider'         => $data['provider'] ?? null,
            'description'      => $data['description'],
            'receipt_path'     => $data['receipt_path'] ?? null,
            'created_by'       => $data['created_by']
        ]);

        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, l.name as location_name, u.name as user_name
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            WHERE t.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = ['id' => $id];

        $allowed = ['type', 'amount', 'transaction_date', 'location_id', 'category', 'provider', 'description', 'receipt_path', 'status'];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = "$key = :$key";
                $params[$key] = $data[$key];
            }
        }

        if (empty($fields)) return false;

        $stmt = $this->db->prepare("UPDATE financial_transactions SET " . implode(', ', $fields) . " WHERE id = :id");
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM financial_transactions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getKPIs()
    {
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN type = 'ingreso' AND status = 'Aprobado' THEN amount ELSE 0 END) as total_ingresos,
                SUM(CASE WHEN type = 'egreso'  AND status = 'Aprobado' THEN amount ELSE 0 END) as total_egresos
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

    public function findRecentByUser($limit = 5, $userId)
    {
        $query = "
            SELECT t.*, l.name as location_name, u.name as user_name 
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            WHERE t.created_by = :user_id
            ORDER BY t.created_at DESC LIMIT :limit
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findTodayRecentByUser($limit = 5, $userId)
    {
        $query = "
            SELECT t.*, l.name as location_name, u.name as user_name 
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            WHERE t.created_by = :user_id 
              AND DATE(t.created_at) = CURDATE()
            ORDER BY t.created_at DESC LIMIT :limit
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
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

    public function findAllByUser($userId)
    {
        $query = "
            SELECT t.*, l.name as location_name, u.name as user_name 
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            WHERE t.created_by = :user_id
            ORDER BY t.transaction_date DESC, t.created_at DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllTodayByUser($userId)
    {
        $query = "
            SELECT t.*, l.name as location_name, u.name as user_name 
            FROM financial_transactions t
            JOIN locations l ON t.location_id = l.id
            JOIN users u ON t.created_by = u.id
            WHERE t.created_by = :user_id 
              AND DATE(t.created_at) = CURDATE()
            ORDER BY t.created_at DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTechKPIs($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'ingreso' AND status = 'Aprobado' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE()) THEN amount ELSE 0 END) as ingresos_mes,
                SUM(CASE WHEN type = 'egreso' AND status = 'Aprobado' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE()) THEN amount ELSE 0 END) as egresos_mes,
                COUNT(id) as total_transacciones
            FROM financial_transactions
            WHERE created_by = :user_id AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTodayTechKPIs($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'ingreso' AND status = 'Aprobado' AND DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as ingresos_hoy,
                SUM(CASE WHEN type = 'egreso' AND status = 'Aprobado' AND DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as egresos_hoy,
                COUNT(id) as total_transacciones_hoy
            FROM financial_transactions
            WHERE created_by = :user_id AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM financial_transactions WHERE status = :status");
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function getMonthlyData($months = 6)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                SUM(CASE WHEN type = 'ingreso' THEN amount ELSE 0 END) as ingresos,
                SUM(CASE WHEN type = 'egreso'  THEN amount ELSE 0 END) as egresos
            FROM financial_transactions
            WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->bindValue(':months', (int) $months, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countLocationsWithoutTransactionsThisMonth()
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM locations l
            WHERE l.id NOT IN (
                SELECT DISTINCT location_id FROM financial_transactions
                WHERE MONTH(transaction_date) = MONTH(CURDATE())
                  AND YEAR(transaction_date)  = YEAR(CURDATE())
            )
        ");
        return (int) $stmt->fetchColumn();
    }
}
