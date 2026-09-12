<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Role;
use PDO;

class RoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `description` TEXT,
            `permissions` JSON,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            error_log("Error creating roles table: " . $e->getMessage());
        }
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM roles ORDER BY created_at ASC");
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = new Role($row);
        }
        return $roles;
    }

    public function findById(int $id): ?Role
    {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Role($row) : null;
    }

    public function create(Role $role): Role
    {
        $sql = "INSERT INTO roles (name, description, permissions) VALUES (:name, :description, :permissions)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => json_encode($role->permissions)
        ]);
        
        $role->id = (int)$this->pdo->lastInsertId();
        return $this->findById($role->id);
    }

    public function update(Role $role): void
    {
        $sql = "UPDATE roles SET name = :name, description = :description, permissions = :permissions WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => json_encode($role->permissions)
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
