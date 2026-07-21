<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\UsuarioEntity;
use PDO;

class UsuarioRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByUsuario(string $usuario): ?UsuarioEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND activo = 1 LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new UsuarioEntity(
            (int) $row['id'],
            $row['usuario'],
            $row['password'],
            $row['nombre'],
            $row['rol'],
            (bool) $row['activo']
        );
    }
}
