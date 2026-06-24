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

    public function getMovimientos(int $inversionistaId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inversionista_movimientos WHERE inversionista_id = :id ORDER BY fecha DESC, created_at DESC");
        $stmt->execute(['id' => $inversionistaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addMovimiento(int $inversionistaId, string $tipo, float $monto, string $fecha, ?string $descripcion): int
    {
        $sql = "INSERT INTO inversionista_movimientos (inversionista_id, tipo, monto, fecha, descripcion) VALUES (:id, :tipo, :monto, :fecha, :descripcion)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $inversionistaId,
            'tipo' => $tipo,
            'monto' => $monto,
            'fecha' => $fecha,
            'descripcion' => $descripcion
        ]);
        
        // Opcionalmente actualizar el capital del inversionista
        if ($tipo === 'INGRESO') {
            $this->pdo->prepare("UPDATE inversionistas SET capital = capital + :monto WHERE id = :id")
                      ->execute(['monto' => $monto, 'id' => $inversionistaId]);
        } elseif ($tipo === 'DESCUENTO') {
            $this->pdo->prepare("UPDATE inversionistas SET capital = capital - :monto WHERE id = :id")
                      ->execute(['monto' => $monto, 'id' => $inversionistaId]);
        }

        return (int)$this->pdo->lastInsertId();
    }

    public function deleteMovimiento(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT inversionista_id, tipo, monto FROM inversionista_movimientos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $mov = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mov) {
            // Revertir el capital
            if ($mov['tipo'] === 'INGRESO') {
                $this->pdo->prepare("UPDATE inversionistas SET capital = capital - :monto WHERE id = :id")
                          ->execute(['monto' => $mov['monto'], 'id' => $mov['inversionista_id']]);
            } elseif ($mov['tipo'] === 'DESCUENTO') {
                $this->pdo->prepare("UPDATE inversionistas SET capital = capital + :monto WHERE id = :id")
                          ->execute(['monto' => $mov['monto'], 'id' => $mov['inversionista_id']]);
            }
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM inversionista_movimientos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
