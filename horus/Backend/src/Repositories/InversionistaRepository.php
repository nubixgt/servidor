<?php
namespace App\Repositories;

use App\Entities\InversionistaEntity;
use App\Utils\Database;
use PDO;

class InversionistaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM inversionistas ORDER BY created_at DESC");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }

    public function findById(int $id): ?InversionistaEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inversionistas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        return $this->mapRowToEntity($row);
    }

    public function create(InversionistaEntity $entity): int
    {
        $sql = "INSERT INTO inversionistas (
            nombre, capital, banco, numero_cuenta, porcentaje, documentos
        ) VALUES (
            :nombre, :capital, :banco, :numero_cuenta, :porcentaje, :documentos
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $entity->nombre,
            'capital' => $entity->capital,
            'banco' => $entity->banco,
            'numero_cuenta' => $entity->numero_cuenta,
            'porcentaje' => $entity->porcentaje,
            'documentos' => $entity->documentos
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, InversionistaEntity $entity): void
    {
        $sql = "UPDATE inversionistas SET 
            nombre = :nombre, 
            capital = :capital, 
            banco = :banco, 
            numero_cuenta = :numero_cuenta, 
            porcentaje = :porcentaje, 
            documentos = :documentos
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'nombre' => $entity->nombre,
            'capital' => $entity->capital,
            'banco' => $entity->banco,
            'numero_cuenta' => $entity->numero_cuenta,
            'porcentaje' => $entity->porcentaje,
            'documentos' => $entity->documentos
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM inversionistas WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function mapRowToEntity(array $row): InversionistaEntity
    {
        return new InversionistaEntity(
            $row['id'],
            $row['nombre'],
            $row['capital'] ? (float)$row['capital'] : null,
            $row['banco'],
            $row['numero_cuenta'],
            $row['porcentaje'] ? (float)$row['porcentaje'] : null,
            $row['documentos'] ?? null,
            $row['created_at'] ?? null
        );
    }
}
