<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Usuario;
use PDO;

class UsuarioRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $data = $stmt->fetch();
        if ($data) {
            return new Usuario($data);
        }
        return null;
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM usuarios ORDER BY created_at DESC");
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new Usuario($row);
        }
        return $results;
    }

    public function create(Usuario $usuario)
    {
        $sql = "INSERT INTO usuarios (username, password_hash, full_name, role, status) 
                VALUES (:username, :password_hash, :full_name, :role, :status)";
        $stmt = $this->db->prepare($sql);
        
        $success = $stmt->execute([
            'username' => $usuario->username,
            'password_hash' => $usuario->password_hash,
            'full_name' => $usuario->full_name,
            'role' => $usuario->role,
            'status' => $usuario->status ?? 'Active'
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    public function update(Usuario $usuario)
    {
        $sql = "UPDATE usuarios 
                SET username = :username, full_name = :full_name, role = :role, status = :status 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'username' => $usuario->username,
            'full_name' => $usuario->full_name,
            'role' => $usuario->role,
            'status' => $usuario->status,
            'id' => $usuario->id
        ]);
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE usuarios SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateLastAccess($id)
    {
        $sql = "UPDATE usuarios SET last_access = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
