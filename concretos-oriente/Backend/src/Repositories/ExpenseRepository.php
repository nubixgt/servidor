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
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithProjects(): array
    {
        $sql = "SELECT e.*, p.nombre as proyecto_nombre, 'Egreso' as transaction_type 
                FROM expenses e 
                LEFT JOIN projects p ON e.proyecto_id = p.id";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO expenses
                    (proyecto_id, contratista_id, tipo_egreso, monto, fecha_egreso, cuenta_origen, numero_cheque, beneficiario, descripcion, comprobante_path)
                VALUES
                    (:proyecto_id, :contratista_id, :tipo_egreso, :monto, :fecha_egreso, :cuenta_origen, :numero_cheque, :beneficiario, :descripcion, NULL)";

        $this->pdo->prepare($sql)->execute([
            'proyecto_id'     => $data['proyecto_id'],
            'contratista_id'  => $data['contratista_id'] ?? null,
            'tipo_egreso'     => $data['tipo_egreso'],
            'monto'           => $data['monto'],
            'fecha_egreso'    => $data['fecha_egreso'],
            'cuenta_origen'   => $data['cuenta_origen'] ?? null,
            'numero_cheque'   => $data['numero_cheque'] ?? null,
            'beneficiario'    => $data['beneficiario'],
            'descripcion'     => $data['descripcion'] ?? null
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
