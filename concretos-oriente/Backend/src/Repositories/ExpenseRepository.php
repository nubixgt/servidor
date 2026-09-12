<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ExpenseRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureColumnsExist();
    }

    private function ensureColumnsExist(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `expenses`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('dependiente', $columns)) {
                try { $this->pdo->exec("ALTER TABLE `expenses` ADD COLUMN `dependiente` VARCHAR(150) NULL"); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithProjects(): array
    {
        $sql = "SELECT e.*, p.nombre as proyecto_nombre, 'Egreso' as transaction_type, 
                       c.empresa as contratista_empresa, c.nombre as contratista_nombre, c.representante as contratista_representante 
                FROM expenses e 
                LEFT JOIN projects p ON e.proyecto_id = p.id
                LEFT JOIN contractors c ON e.contratista_id = c.id";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO expenses
                    (proyecto_id, contratista_id, tipo_egreso, monto, fecha_egreso, cuenta_origen, numero_cheque, beneficiario, descripcion, dependiente, comprobante_path)
                VALUES
                    (:proyecto_id, :contratista_id, :tipo_egreso, :monto, :fecha_egreso, :cuenta_origen, :numero_cheque, :beneficiario, :descripcion, :dependiente, NULL)";

        $this->pdo->prepare($sql)->execute([
            'proyecto_id'     => $data['proyecto_id'],
            'contratista_id'  => $data['contratista_id'] ?? null,
            'tipo_egreso'     => $data['tipo_egreso'],
            'monto'           => $data['monto'],
            'fecha_egreso'    => $data['fecha_egreso'],
            'cuenta_origen'   => $data['cuenta_origen'] ?? null,
            'numero_cheque'   => $data['numero_cheque'] ?? null,
            'beneficiario'    => $data['beneficiario'],
            'descripcion'     => $data['descripcion'] ?? null,
            'dependiente'     => $data['dependiente'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateComprobante(int $id, string $path): void
    {
        $this->pdo->prepare("UPDATE expenses SET comprobante_path = :comp WHERE id = :id")
             ->execute(['comp' => $path, 'id' => $id]);
    }

    public function addRecord(int $expenseId, string $descripcion, float $monto): void
    {
        $sql = "INSERT INTO expense_records (expense_id, descripcion, monto) VALUES (:expense_id, :descripcion, :monto)";
        $this->pdo->prepare($sql)->execute([
            'expense_id'  => $expenseId,
            'descripcion' => $descripcion,
            'monto'       => $monto
        ]);
    }
}
