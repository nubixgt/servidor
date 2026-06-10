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
        $sql = "INSERT INTO bank_accounts (nombre_banco, numero_cuenta, tipo_cuenta, moneda, activa, saldo_inicial, saldo_actual)
                VALUES (:nombre_banco, :numero_cuenta, :tipo_cuenta, :moneda, :activa, :saldo_inicial, :saldo_actual)";
        
        $this->pdo->prepare($sql)->execute([
            'nombre_banco'  => $data['nombre_banco'],
            'numero_cuenta' => $data['numero_cuenta'],
            'tipo_cuenta'   => $data['tipo_cuenta'],
            'moneda'        => $data['moneda'] ?? 'GTQ',
            'activa'        => $data['activa'] ?? 1,
            'saldo_inicial' => $data['saldo_inicial'] ?? 0,
            'saldo_actual'  => $data['saldo_inicial'] ?? 0
        ]);
    }

    public function findTransactions(int $accountId): array
    {
        // First get the account name to match string-based records
        $stmt = $this->pdo->prepare("SELECT CONCAT(nombre_banco, ' - ', numero_cuenta) as name FROM bank_accounts WHERE id = :id");
        $stmt->execute(['id' => $accountId]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$acc) return [];
        $cuentaName = $acc['name'];

        $sqlIncomes = "SELECT id, 'in' as type, fecha_ingreso as date, 'INGRESO' as bankDesc, descripcion as detail, monto as amount, cuenta_bancaria as account
                       FROM incomes WHERE cuenta_bancaria = :cuenta";
        $stmtIncomes = $this->pdo->prepare($sqlIncomes);
        $stmtIncomes->execute(['cuenta' => $cuentaName]);
        $incomes = $stmtIncomes->fetchAll(PDO::FETCH_ASSOC);

        $sqlExpenses = "SELECT id, 'out' as type, fecha_egreso as date, 'EGRESO' as bankDesc, descripcion as detail, monto as amount, cuenta_origen as account
                        FROM expenses WHERE cuenta_origen = :cuenta";
        $stmtExpenses = $this->pdo->prepare($sqlExpenses);
        $stmtExpenses->execute(['cuenta' => $cuentaName]);
        $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

        $transactions = array_merge($incomes, $expenses);
        usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        
        return $transactions;
    }
    
    public function updateBalance(string $cuentaName, float $amountChange): void
    {
        $stmt = $this->pdo->prepare("UPDATE bank_accounts SET saldo_actual = saldo_actual + :change WHERE CONCAT(nombre_banco, ' - ', numero_cuenta) = :name");
        $stmt->execute([
            'change' => $amountChange,
            'name' => $cuentaName
        ]);
    }

}
