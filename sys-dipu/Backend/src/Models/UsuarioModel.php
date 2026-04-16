<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class UsuarioModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT id, nombre_completo, usuario, password_hash, rol, categoria_asignada, estado FROM usuarios WHERE usuario = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT id, nombre_completo, usuario, rol, categoria_asignada, estado, DATE_FORMAT(ultimo_acceso, '%Y-%m-%d %H:%i') as ultimo_acceso FROM usuarios ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, categoria_asignada) VALUES (:nombre_completo, :usuario, :password_hash, :rol, :categoria_asignada)");
        return $stmt->execute([
            ':nombre_completo' => $data['nombre_completo'],
            ':usuario' => $data['usuario'],
            ':password_hash' => $data['password_hash'],
            ':rol' => $data['rol'],
            ':categoria_asignada' => $data['categoria_asignada']
        ]);
    }

    public function toggleStatus($id, $newStatus)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :newStatus WHERE id = :id");
        $stmt->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateLastAccess($id)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $query = "UPDATE usuarios SET nombre_completo = :nombre, rol = :rol, categoria_asignada = :categoria";
        $params = [
            ':nombre' => $data['nombre_completo'],
            ':rol' => $data['rol'],
            ':categoria' => $data['categoria_asignada'],
            ':id' => $id
        ];

        if (!empty($data['password_hash'])) {
            $query .= ", password_hash = :password";
            $params[':password'] = $data['password_hash'];
        }

        $query .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
}
