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
        $this->autoMigrate();
    }

    private function autoMigrate(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS recurrents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                concepto VARCHAR(255) NOT NULL,
                descripcion TEXT NULL,
                monto DECIMAL(10,2) NOT NULL DEFAULT 0,
                dia_pago INT NULL,
                created_by INT NOT NULL,
                beneficiario VARCHAR(255) NULL,
                cuenta VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $columns = $this->pdo->query("SHOW COLUMNS FROM recurrents")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('beneficiario', $columns)) {
            $this->pdo->exec("ALTER TABLE recurrents ADD COLUMN beneficiario VARCHAR(255) NULL");
        }
        if (!in_array('cuenta', $columns)) {
            $this->pdo->exec("ALTER TABLE recurrents ADD COLUMN cuenta VARCHAR(255) NULL");
        }
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
        $sql = "INSERT INTO recurrents (concepto, descripcion, monto, dia_pago, created_by, beneficiario, cuenta) 
                VALUES (:concepto, :descripcion, :monto, :dia_pago, :created_by, :beneficiario, :cuenta)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $data['concepto'],
            'descripcion'=> $data['descripcion'] ?? null,
            'monto'      => $data['monto'],
            'dia_pago'   => $data['dia_pago'] ?? null,
            'created_by' => $data['created_by'],
            'beneficiario'=> $data['beneficiario'] ?? null,
            'cuenta'     => $data['cuenta'] ?? null
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE recurrents 
                SET concepto = :concepto, descripcion = :descripcion, monto = :monto, dia_pago = :dia_pago,
                    beneficiario = :beneficiario, cuenta = :cuenta 
                WHERE id = :id AND created_by = :created_by";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'concepto'   => $data['concepto'],
            'descripcion'=> $data['descripcion'] ?? null,
            'monto'      => $data['monto'],
            'dia_pago'   => $data['dia_pago'] ?? null,
            'beneficiario'=> $data['beneficiario'] ?? null,
            'cuenta'     => $data['cuenta'] ?? null,
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
