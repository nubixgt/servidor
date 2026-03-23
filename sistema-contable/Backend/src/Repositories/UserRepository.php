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
}
