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
}
