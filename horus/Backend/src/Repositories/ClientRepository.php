<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\ClientEntity;
use PDO;

class ClientRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT c.*, r.nombre AS refiere_nombre FROM clientes c LEFT JOIN referidos r ON c.refiere = r.id ORDER BY c.id DESC");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $clients = [];
        foreach ($results as $row) {
            $clients[] = new ClientEntity(
                $row['id'],
                $row['fecha'],
                $row['cliente'],
                $row['refiere'],
                $row['capital'] !== null ? (float)$row['capital'] : null,
                $row['plazo'],
                $row['porcentaje'] !== null ? (float)$row['porcentaje'] : null,
                $row['porcentaje_referido'] !== null ? (float)$row['porcentaje_referido'] : null,
                $row['interes_pagar'] !== null ? (float)$row['interes_pagar'] : null,
                $row['observaciones'],
                $row['documentacion'] ?? null,
                $row['refiere_nombre'] ?? null
            );
        }

        return $clients;
    }

    public function findById(int $id): ?ClientEntity
    {
        $stmt = $this->pdo->prepare("SELECT c.*, r.nombre AS refiere_nombre FROM clientes c LEFT JOIN referidos r ON c.refiere = r.id WHERE c.id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new ClientEntity(
            $row['id'],
            $row['fecha'],
            $row['cliente'],
            $row['refiere'],
            $row['capital'] !== null ? (float)$row['capital'] : null,
            $row['plazo'],
            $row['porcentaje'] !== null ? (float)$row['porcentaje'] : null,
            $row['porcentaje_referido'] !== null ? (float)$row['porcentaje_referido'] : null,
            $row['interes_pagar'] !== null ? (float)$row['interes_pagar'] : null,
            $row['observaciones'],
            $row['documentacion'] ?? null,
            $row['refiere_nombre'] ?? null
        );
    }

    public function create(ClientEntity $entity): int
    {
        $sql = "INSERT INTO clientes (
            fecha, cliente, refiere, capital, plazo, porcentaje, porcentaje_referido,
            interes_pagar, observaciones, documentacion
        ) VALUES (
            :fecha, :cliente, :refiere, :capital, :plazo, :porcentaje, :porcentaje_referido,
            :interes_pagar, :observaciones, :documentacion
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'fecha' => $entity->fecha,
            'cliente' => $entity->cliente,
            'refiere' => $entity->refiere,
            'capital' => $entity->capital,
            'plazo' => $entity->plazo,
            'porcentaje' => $entity->porcentaje,
            'porcentaje_referido' => $entity->porcentajeReferido,
            'interes_pagar' => $entity->interesPagar,
            'observaciones' => $entity->observaciones,
            'documentacion' => $entity->documentacion
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, ClientEntity $entity): bool
    {
        $sql = "UPDATE clientes SET 
            fecha = :fecha, 
            cliente = :cliente, 
            refiere = :refiere, 
            capital = :capital, 
            plazo = :plazo, 
            porcentaje = :porcentaje, 
            porcentaje_referido = :porcentaje_referido,
            interes_pagar = :interes_pagar, 
            observaciones = :observaciones,
            documentacion = :documentacion
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'fecha' => $entity->fecha,
            'cliente' => $entity->cliente,
            'refiere' => $entity->refiere,
            'capital' => $entity->capital,
            'plazo' => $entity->plazo,
            'porcentaje' => $entity->porcentaje,
            'porcentaje_referido' => $entity->porcentajeReferido,
            'interes_pagar' => $entity->interesPagar,
            'observaciones' => $entity->observaciones,
            'documentacion' => $entity->documentacion
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
