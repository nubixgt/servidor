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

    public function findTransactions(?string $cuenta): array
    {
        $sqlIncomes = "SELECT id, 'in' as type, fecha_ingreso as date, 'INGRESO' as bankDesc, descripcion as detail, monto as amount, cuenta_bancaria as account
                       FROM incomes";
        if ($cuenta) $sqlIncomes .= " WHERE cuenta_bancaria LIKE :cuenta";

        $stmtIncomes = $this->pdo->prepare($sqlIncomes);
        if ($cuenta) $stmtIncomes->execute(['cuenta' => "%$cuenta%"]);
        else $stmtIncomes->execute();
        $incomes = $stmtIncomes->fetchAll(PDO::FETCH_ASSOC);

        $sqlExpenses = "SELECT id, 'out' as type, fecha_egreso as date, 'EGRESO' as bankDesc, descripcion as detail, monto as amount, cuenta_origen as account
                        FROM expenses";
        if ($cuenta) $sqlExpenses .= " WHERE cuenta_origen LIKE :cuenta";

        $stmtExpenses = $this->pdo->prepare($sqlExpenses);
        if ($cuenta) $stmtExpenses->execute(['cuenta' => "%$cuenta%"]);
        else $stmtExpenses->execute();
        $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

        return array_merge($incomes, $expenses);
    }
}
