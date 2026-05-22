<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class BankReconciliationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function create(array $data): void
    {
        $sql = "INSERT INTO bank_reconciliations (bank_account_id, periodo, saldo_banco, partidas_conciliatorias)
                VALUES (:bank_account_id, :periodo, :saldo_banco, :partidas_conciliatorias)";
        
        $this->pdo->prepare($sql)->execute([
            'bank_account_id'         => $data['bank_account_id'],
            'periodo'                 => $data['periodo'],
            'saldo_banco'             => $data['saldo_banco'],
            'partidas_conciliatorias' => $data['partidas_conciliatorias']
        ]);
    }
}
