<?php
namespace App\Repositories;

use App\Entities\PrestamoEntity;
use App\Utils\Database;
use PDO;

class PrestamoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT p.*, c.nombre as cliente_nombre 
                FROM prestamos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id 
                ORDER BY p.created_at DESC";
        $stmt = $this->pdo->query($sql);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = $this->mapRowToEntity($row);
            // Optionally, you can attach the client name dynamically if you extend the entity,
            // or just rely on the frontend fetching the client details separately.
            $results[] = $entity;
        }
        return $results;
    }

    public function findById(int $id): ?PrestamoEntity
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prestamos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        return $this->mapRowToEntity($row);
    }

    public function create(PrestamoEntity $entity): int
    {
        $sql = "INSERT INTO prestamos (
            cliente_id, monto_principal, tasa, tipo_interes, plazo, fecha_desembolso,
            total_intereses, total_seguro, costo_total, estado
        ) VALUES (
            :cliente_id, :monto_principal, :tasa, :tipo_interes, :plazo, :fecha_desembolso,
            :total_intereses, :total_seguro, :costo_total, :estado
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'cliente_id' => $entity->cliente_id,
            'monto_principal' => $entity->monto_principal,
            'tasa' => $entity->tasa,
            'tipo_interes' => $entity->tipo_interes,
            'plazo' => $entity->plazo,
            'fecha_desembolso' => $entity->fecha_desembolso,
            'total_intereses' => $entity->total_intereses,
            'total_seguro' => $entity->total_seguro,
            'costo_total' => $entity->costo_total,
            'estado' => $entity->estado
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, PrestamoEntity $entity): void
    {
        $sql = "UPDATE prestamos SET 
            cliente_id = :cliente_id, 
            monto_principal = :monto_principal, 
            tasa = :tasa, 
            tipo_interes = :tipo_interes, 
            plazo = :plazo, 
            fecha_desembolso = :fecha_desembolso,
            total_intereses = :total_intereses, 
            total_seguro = :total_seguro, 
            costo_total = :costo_total,
            estado = :estado
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'cliente_id' => $entity->cliente_id,
            'monto_principal' => $entity->monto_principal,
            'tasa' => $entity->tasa,
            'tipo_interes' => $entity->tipo_interes,
            'plazo' => $entity->plazo,
            'fecha_desembolso' => $entity->fecha_desembolso,
            'total_intereses' => $entity->total_intereses,
            'total_seguro' => $entity->total_seguro,
            'costo_total' => $entity->costo_total,
            'estado' => $entity->estado
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM prestamos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function mapRowToEntity(array $row): PrestamoEntity
    {
        return new PrestamoEntity(
            $row['id'],
            $row['cliente_id'],
            (float)$row['monto_principal'],
            (float)$row['tasa'],
            $row['tipo_interes'],
            (int)$row['plazo'],
            $row['fecha_desembolso'],
            (float)$row['total_intereses'],
            (float)$row['total_seguro'],
            (float)$row['costo_total'],
            $row['estado'],
            $row['created_at']
        );
    }
}
