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
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $stmt->execute(['usuario' => $username]);
        return $stmt->fetchObject(User::class);
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM usuarios");
        return $stmt->fetchAll(PDO::FETCH_CLASS, User::class);
    }

    public function create(User $user)
    {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, rol, estado) VALUES (:nombre, :usuario, :contrasena, :rol, :estado)");
        $stmt->execute([
            'nombre' => $user->nombre,
            'usuario' => $user->usuario,
            'contrasena' => $user->contrasena,
            'rol' => $user->rol ?? 'vendedor',
            'estado' => $user->estado ?? 'activo'
        ]);
        return $this->db->lastInsertId();
    }

    public function getRoleWithPrivileges(int $userId)
    {
        // 1. Get User's Role Name
        $stmtRole = $this->db->prepare("
            SELECT rol 
            FROM usuarios
            WHERE id = :user_id
        ");
        $stmtRole->execute(['user_id' => $userId]);
        $roleResult = $stmtRole->fetch(PDO::FETCH_ASSOC);

        $roleName = $roleResult ? $roleResult['rol'] : 'guest';

        // 2. We don't have privileges table in Emagro database, so we mock privileges based on role
        $privileges = [];
        if ($roleName === 'admin') {
            $privileges = ['view_dashboard', 'manage_users', 'manage_clients', 'manage_sales', 'manage_inventory'];
        } else if ($roleName === 'vendedor') {
            $privileges = ['view_dashboard', 'manage_clients', 'manage_sales'];
        }

        return [
            'role' => $roleName,
            'privileges' => $privileges
        ];
    }

    public function update(User $user)
    {
        if (!empty($user->contrasena)) {
            $stmt = $this->db->prepare("UPDATE usuarios SET nombre = :nombre, usuario = :usuario, contrasena = :contrasena, rol = :rol, estado = :estado WHERE id = :id");
            return $stmt->execute([
                'id' => $user->id,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'contrasena' => $user->contrasena,
                'rol' => $user->rol,
                'estado' => $user->estado
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE usuarios SET nombre = :nombre, usuario = :usuario, rol = :rol, estado = :estado WHERE id = :id");
            return $stmt->execute([
                'id' => $user->id,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'rol' => $user->rol,
                'estado' => $user->estado
            ]);
        }
    }

    public function updateStatus($id, $estado)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :estado WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'estado' => $estado
        ]);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchObject(User::class);
    }
}
