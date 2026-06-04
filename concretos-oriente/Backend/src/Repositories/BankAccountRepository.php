<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class BankAccountRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM bank_accounts ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $sql = "INSERT INTO bank_accounts (nombre_banco, numero_cuenta, tipo_cuenta, moneda, activa)
                VALUES (:nombre_banco, :numero_cuenta, :tipo_cuenta, :moneda, :activa)";
        
        $this->pdo->prepare($sql)->execute([
            'nombre_banco'  => $data['nombre_banco'],
            'numero_cuenta' => $data['numero_cuenta'],
            'tipo_cuenta'   => $data['tipo_cuenta'],
            'moneda'        => $data['moneda'] ?? 'GTQ',
            'activa'        => $data['activa'] ?? 1
        ]);
    }

}
