<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Departamento;
use PDO;

class DepartamentoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, nombre FROM departamentos ORDER BY nombre ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn($row) => new Departamento((int) $row['id'], $row['nombre']),
            $rows
        );
    }

    public function existsById(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM departamentos WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }
}
