<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class IncomeRepository
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
        $sql = "SELECT i.*, p.nombre as proyecto_nombre, 'Ingreso' as transaction_type 
                FROM incomes i 
                LEFT JOIN projects p ON i.proyecto_id = p.id";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO incomes 
                    (proyecto_id, tipo_ingreso, monto, fecha_ingreso, cuenta_bancaria, numero_cheque, pagador, descripcion, comprobante_path)
                VALUES 
                    (:proyecto_id, :tipo_ingreso, :monto, :fecha_ingreso, :cuenta_bancaria, :numero_cheque, :pagador, :descripcion, NULL)";
        
        $this->pdo->prepare($sql)->execute([
            'proyecto_id'     => $data['proyecto_id'],
            'tipo_ingreso'    => $data['tipo_ingreso'],
            'monto'           => $data['monto'],
            'fecha_ingreso'   => $data['fecha_ingreso'],
            'cuenta_bancaria' => $data['cuenta_bancaria'],
            'numero_cheque'   => $data['numero_cheque'] ?? null,
            'pagador'         => $data['pagador'] ?? null,
            'descripcion'     => $data['descripcion'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateComprobante(int $id, string $path): void
    {
        $this->pdo->prepare("UPDATE incomes SET comprobante_path = :comp WHERE id = :id")
             ->execute(['comp' => $path, 'id' => $id]);
    }
}
