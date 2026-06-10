<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\UserEntity;
use PDO;

class UserRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUsername(string $username): ?UserEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new UserEntity(
            $row['id'],
            $row['username'],
            $row['password'],
            $row['rol'] ?? 'admin',
            isset($row['activo']) ? (int)$row['activo'] : 1
        );
    }
}
