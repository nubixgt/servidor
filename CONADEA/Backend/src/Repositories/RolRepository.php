<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Rol;
use PDO;

class RolRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByNombre(string $nombre): ?Rol
    {
        $stmt = $this->pdo->prepare("SELECT id, nombre FROM roles WHERE nombre = :nombre LIMIT 1");
        $stmt->execute(['nombre' => $nombre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new Rol((int) $row['id'], $row['nombre']) : null;
    }
}
