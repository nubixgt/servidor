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
}
