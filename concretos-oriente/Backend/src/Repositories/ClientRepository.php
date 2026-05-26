<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ClientRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*, 
                       COUNT(p.id) as projects_count, 
                       IFNULL(SUM(p.presupuesto), 0) as portfolio_value 
                FROM clients c
                LEFT JOIN projects p ON c.id = p.cliente_id
                GROUP BY c.id
                ORDER BY c.company_name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM clients WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO clients (company_name, ruc, status, contact_name, email, phone, address)
                VALUES (:company_name, :ruc, :status, :contact_name, :email, :phone, :address)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'company_name' => $data['company_name'],
            'ruc'          => $data['ruc'] ?? null,
            'status'       => $data['status'] ?? 'active',
            'contact_name' => $data['contact_name'],
            'email'        => $data['email'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'address'      => $data['address'] ?? null,
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE clients SET 
                    company_name = :company_name,
                    ruc = :ruc,
                    status = :status,
                    contact_name = :contact_name,
                    email = :email,
                    phone = :phone,
                    address = :address
                WHERE id = :id";
                
        $data['id'] = $id;
        $this->pdo->prepare($sql)->execute($data);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM clients WHERE id = :id")->execute(['id' => $id]);
    }
}
