<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\UserEntity;
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
        $stmt = $this->db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return new UserEntity(
                $row['id'],
                $row['username'],
                $row['password_hash'],
                $row['role']
            );
        }
        
        return null;
    }
}
