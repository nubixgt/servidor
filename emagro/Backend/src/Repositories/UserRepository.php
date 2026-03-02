<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\User;
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
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetchObject(User::class);
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM users");
        return $stmt->fetchAll(PDO::FETCH_CLASS, User::class);
    }

    public function create(User $user)
    {
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, email, role_id, status, avatar_url) VALUES (:username, :password_hash, :email, :role_id, :status, :avatar_url)");
        $stmt->execute([
            'username' => $user->username,
            'password_hash' => $user->password_hash,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'status' => $user->status ?? 'Activo',
            'avatar_url' => $user->avatar_url
        ]);
        return $this->db->lastInsertId();
    }

    public function getRoleWithPrivileges(int $userId)
    {
        // 1. Get User's Role Name
        $stmtRole = $this->db->prepare("
            SELECT r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id
        ");
        $stmtRole->execute(['user_id' => $userId]);
        $roleResult = $stmtRole->fetch(PDO::FETCH_ASSOC);

        $roleName = $roleResult ? $roleResult['role_name'] : 'guest';

        // 2. Get User's Privileges Array
        $stmtPrivs = $this->db->prepare("
            SELECT p.name as privilege_name
            FROM users u
            JOIN role_privileges rp ON u.role_id = rp.role_id
            JOIN privileges p ON rp.privilege_id = p.id
            WHERE u.id = :user_id
        ");
        $stmtPrivs->execute(['user_id' => $userId]);
        $privileges = $stmtPrivs->fetchAll(PDO::FETCH_COLUMN);

        return [
            'role' => $roleName,
            'privileges' => $privileges
        ];
    }
}
