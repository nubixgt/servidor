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

    public function findAllByUser(string $username): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM recurrents WHERE creado_por = :username ORDER BY id DESC");
        $stmt->execute(['username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO recurrents (concepto, descripcion, monto, dia_pago, creado_por) 
                VALUES (:concepto, :descripcion, :monto, :dia_pago, :creado_por)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $data['concepto'],
            'descripcion'=> $data['descripcion'],
            'monto'      => $data['monto'],
            'dia_pago'   => $data['dia_pago'],
            'creado_por' => $data['creado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE recurrents 
                SET concepto = :concepto, descripcion = :descripcion, monto = :monto, dia_pago = :dia_pago 
                WHERE id = :id AND creado_por = :creado_por";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $data['concepto'],
            'descripcion'=> $data['descripcion'],
            'monto'      => $data['monto'],
            'dia_pago'   => $data['dia_pago'],
            'id'         => $id,
            'creado_por' => $data['creado_por']
        ]);
    }

    public function delete(int $id, string $username): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM recurrents WHERE id = :id AND creado_por = :username");
        $stmt->execute([
            'id' => $id,
            'username' => $username
        ]);
    }
}
