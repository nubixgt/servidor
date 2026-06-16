<?php
namespace App\Repositories;

use App\Entities\EgresoEntity;
use App\Entities\EgresoRegistroEntity;
use App\Utils\Database;
use PDO;

class EgresoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        // Join with gastos_recurrentes to get the name if needed, but we can just return raw for now.
        $sql = "SELECT e.*, g.concepto as recurrente_nombre 
                FROM egresos e 
                LEFT JOIN gastos_recurrentes g ON e.recurrente_id = g.id 
                ORDER BY e.created_at DESC";
        $stmt = $this->pdo->query($sql);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = $this->mapRowToEntity($row);
            // Fetch registros
            $entity->registros = $this->findRegistrosByEgresoId($entity->id);
            // We can attach the recurrente_nombre dynamically if needed, 
            // but the entity only has recurrente_id. We'll return it as an array eventually.
            // Let's pass it to the frontend via controller mapping or let it be.
            $results[] = [
                'egreso' => $entity,
                'recurrente_nombre' => $row['recurrente_nombre']
            ];
        }
        return $results;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT e.*, g.concepto as recurrente_nombre 
                FROM egresos e 
                LEFT JOIN gastos_recurrentes g ON e.recurrente_id = g.id 
                WHERE e.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        $entity = $this->mapRowToEntity($row);
        $entity->registros = $this->findRegistrosByEgresoId($entity->id);

        return [
            'egreso' => $entity,
            'recurrente_nombre' => $row['recurrente_nombre']
        ];
    }

    public function findRegistrosByEgresoId(int $egresoId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM egreso_registros WHERE egreso_id = :egreso_id ORDER BY id ASC");
        $stmt->execute(['egreso_id' => $egresoId]);
        $registros = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $registros[] = new EgresoRegistroEntity(
                $row['id'],
                $row['egreso_id'],
                $row['descripcion'],
                (float)$row['monto'],
                $row['created_at']
            );
        }
        return $registros;
    }

    public function create(EgresoEntity $entity): int
    {
        $sql = "INSERT INTO egresos (
            recurrente_id, tipo_egreso, fecha, referencia, pagador, comprobante, descripcion_concepto
        ) VALUES (
            :recurrente_id, :tipo_egreso, :fecha, :referencia, :pagador, :comprobante, :descripcion_concepto
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'recurrente_id' => $entity->recurrente_id,
            'tipo_egreso' => $entity->tipo_egreso,
            'fecha' => $entity->fecha,
            'referencia' => $entity->referencia,
            'pagador' => $entity->pagador,
            'comprobante' => $entity->comprobante,
            'descripcion_concepto' => $entity->descripcion_concepto
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, EgresoEntity $entity): void
    {
        $sql = "UPDATE egresos SET 
            recurrente_id = :recurrente_id, 
            tipo_egreso = :tipo_egreso, 
            fecha = :fecha, 
            referencia = :referencia, 
            pagador = :pagador, 
            comprobante = :comprobante,
            descripcion_concepto = :descripcion_concepto
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'recurrente_id' => $entity->recurrente_id,
            'tipo_egreso' => $entity->tipo_egreso,
            'fecha' => $entity->fecha,
            'referencia' => $entity->referencia,
            'pagador' => $entity->pagador,
            'comprobante' => $entity->comprobante,
            'descripcion_concepto' => $entity->descripcion_concepto
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM egresos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function clearRegistros(int $egresoId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM egreso_registros WHERE egreso_id = :egreso_id");
        $stmt->execute(['egreso_id' => $egresoId]);
    }

    public function createRegistro(EgresoRegistroEntity $registro): int
    {
        $sql = "INSERT INTO egreso_registros (egreso_id, descripcion, monto) VALUES (:egreso_id, :descripcion, :monto)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'egreso_id' => $registro->egreso_id,
            'descripcion' => $registro->descripcion,
            'monto' => $registro->monto
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function mapRowToEntity(array $row): EgresoEntity
    {
        return new EgresoEntity(
            $row['id'],
            $row['recurrente_id'] ? (int)$row['recurrente_id'] : null,
            $row['tipo_egreso'],
            $row['fecha'],
            $row['referencia'],
            $row['pagador'],
            $row['comprobante'],
            $row['descripcion_concepto'],
            $row['created_at'],
            [] // will be populated
        );
    }
}
