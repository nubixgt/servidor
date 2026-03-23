<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND status = 'Activo'");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findAll()
    {
        $stmt = $this->db->query("SELECT id, username, name, email, role, status, last_login_at FROM users ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPrivilegesByRole($role)
    {
        $stmt = $this->db->prepare("
            SELECT p.name 
            FROM role_privileges rp
            JOIN privileges p ON rp.privilege_id = p.id
            WHERE rp.role_name = :role
        ");
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT id, username, name, email, role, status FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password_hash, name, email, role, status) 
                  VALUES (:username, :password_hash, :name, :email, :role, :status)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => $data['username'],
            'password_hash' => $hash,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'] ?? 'Activo'
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $query = "UPDATE users SET 
                    username = :username, 
                    name = :name, 
                    email = :email, 
                    role = :role, 
                    status = :status";
                    
        $params = [
            'id' => $id,
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status']
        ];

        if (isset($data['password']) && $data['password'] !== '') {
            $query .= ", password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $query .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function updateLastLogin($id)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
