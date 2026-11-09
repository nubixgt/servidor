<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\User;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    private static function map(array $r): User
    {
        return new User(
            (int) $r['id'],
            $r['username'],
            $r['password_hash'],
            $r['full_name'],
            $r['role'],
            (bool) $r['active'],
            $r['created_at']
        );
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::map($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ? self::map($row) : null;
    }

    /** @return User[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM users ORDER BY active DESC, role ASC, full_name ASC');
        return array_map([self::class, 'map'], $stmt->fetchAll());
    }

    /** Lanza PDOException (23000) si el usuario ya existe. */
    public function create(string $username, string $passwordHash, string $fullName, string $role): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, full_name, role, active, created_at) VALUES (?, ?, ?, ?, 1, ?)'
        );
        $stmt->execute([$username, $passwordHash, $fullName, $role, gmdate('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Actualiza solo las columnas permitidas presentes en $fields. */
    public function update(int $id, array $fields): void
    {
        $allowed = ['full_name', 'role', 'active', 'password_hash'];
        $sets = [];
        $values = [];
        foreach ($fields as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[] = "$col = ?";
                $values[] = $val;
            }
        }
        if (!$sets) {
            return;
        }
        $values[] = $id;
        $this->pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($values);
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1")->fetchColumn();
    }
}
