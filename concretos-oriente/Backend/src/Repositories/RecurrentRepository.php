<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class RecurrentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM recurrents ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM recurrents WHERE created_by = :userId ORDER BY id DESC");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO recurrents (concepto, descripcion, monto, dia_pago, created_by) 
                VALUES (:concepto, :descripcion, :monto, :dia_pago, :created_by)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $data['concepto'],
            'descripcion'=> $data['descripcion'],
            'monto'      => $data['monto'],
            'dia_pago'   => $data['dia_pago'],
            'created_by' => $data['created_by']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE recurrents 
                SET concepto = :concepto, descripcion = :descripcion, monto = :monto, dia_pago = :dia_pago 
                WHERE id = :id AND created_by = :created_by";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $data['concepto'],
            'descripcion'=> $data['descripcion'],
            'monto'      => $data['monto'],
            'dia_pago'   => $data['dia_pago'],
            'id'         => $id,
            'created_by' => $data['created_by']
        ]);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM recurrents WHERE id = :id AND created_by = :userId");
        $stmt->execute([
            'id' => $id,
            'userId' => $userId
        ]);
    }
}
