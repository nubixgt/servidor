<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class PuestosRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, nombre FROM tipo_puestos ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $nombre): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO tipo_puestos (nombre) VALUES (:nombre)");
        $stmt->execute(['nombre' => $nombre]);
        return (int) $this->pdo->lastInsertId();
    }

    public function existsByName(string $nombre): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tipo_puestos WHERE nombre = :nombre");
        $stmt->execute(['nombre' => $nombre]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
