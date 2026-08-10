<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Municipio;
use PDO;

class MunicipioRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findByDepartamento(int $departamentoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, departamento_id, nombre
             FROM municipios
             WHERE departamento_id = :departamento_id
             ORDER BY nombre ASC"
        );
        $stmt->execute(['departamento_id' => $departamentoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn($row) => new Municipio((int) $row['id'], (int) $row['departamento_id'], $row['nombre']),
            $rows
        );
    }

    public function existsByIdAndDepartamento(int $municipioId, int $departamentoId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM municipios WHERE id = :id AND departamento_id = :departamento_id LIMIT 1"
        );
        $stmt->execute(['id' => $municipioId, 'departamento_id' => $departamentoId]);

        return (bool) $stmt->fetchColumn();
    }
}
