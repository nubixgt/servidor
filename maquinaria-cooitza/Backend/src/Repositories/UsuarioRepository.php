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
}
