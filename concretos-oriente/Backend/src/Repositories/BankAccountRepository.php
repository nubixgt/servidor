<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;
use Exception;

class BankAccountRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM bank_accounts ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function create(array $data): int
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
            'saldo_actual'  => $data['saldo_actual'] ?? ($data['saldo_inicial'] ?? 0)
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE bank_accounts SET 
                    nombre_banco = :nombre_banco,
                    numero_cuenta = :numero_cuenta,
                    tipo_cuenta = :tipo_cuenta,
                    moneda = :moneda,
                    activa = :activa
                WHERE id = :id";
        
        $this->pdo->prepare($sql)->execute([
            'id'            => $id,
            'nombre_banco'  => $data['nombre_banco'],
            'numero_cuenta' => $data['numero_cuenta'],
            'tipo_cuenta'   => $data['tipo_cuenta'],
            'moneda'        => $data['moneda'] ?? 'GTQ',
            'activa'        => $data['activa'] ?? 1
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM bank_accounts WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function findTransactions(int $accountId): array
    {
        $stmt = $this->pdo->prepare("SELECT CONCAT(nombre_banco, ' - ', numero_cuenta) as name, nombre_banco, numero_cuenta, saldo_actual FROM bank_accounts WHERE id = :id");
        $stmt->execute(['id' => $accountId]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$acc) return [];
        $cuentaName = $acc['name'];
        $bancoNombre = $acc['nombre_banco'];

        $sqlIncomes = "SELECT id, 'in' as type, fecha_ingreso as date, 'Ingreso' as bankDesc, 
                              COALESCE(descripcion, CONCAT('Ingreso - ', tipo_ingreso)) as detail, 
                              monto as amount, cuenta_bancaria as account, :banco as bank_name
                       FROM incomes 
                       WHERE cuenta_bancaria = :cuenta OR cuenta_bancaria LIKE :likePattern";
        $stmtIncomes = $this->pdo->prepare($sqlIncomes);
        $stmtIncomes->execute([
            'cuenta' => $cuentaName,
            'likePattern' => '%' . $acc['numero_cuenta'] . '%',
            'banco' => $bancoNombre
        ]);
        $incomes = $stmtIncomes->fetchAll(PDO::FETCH_ASSOC);

        $sqlExpenses = "SELECT id, 'out' as type, fecha_egreso as date, 'Egreso' as bankDesc, 
                               COALESCE(descripcion, CONCAT('Egreso - ', beneficiario)) as detail, 
                               monto as amount, cuenta_origen as account, :banco as bank_name
                        FROM expenses 
                        WHERE cuenta_origen = :cuenta OR cuenta_origen LIKE :likePattern";
        $stmtExpenses = $this->pdo->prepare($sqlExpenses);
        $stmtExpenses->execute([
            'cuenta' => $cuentaName,
            'likePattern' => '%' . $acc['numero_cuenta'] . '%',
            'banco' => $bancoNombre
        ]);
        $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

        $transactions = array_merge($incomes, $expenses);
        usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        
        return $transactions;
    }

    public function getAllTransactions(int $limit = 50): array
    {
        $sqlIncomes = "SELECT i.id, 'in' as type, i.fecha_ingreso as date, 'Ingreso' as bankDesc, 
                              COALESCE(i.descripcion, CONCAT('Ingreso - ', i.tipo_ingreso)) as detail, 
                              i.monto as amount, i.cuenta_bancaria as account,
                              p.nombre as project_name
                       FROM incomes i
                       LEFT JOIN projects p ON i.proyecto_id = p.id
                       ORDER BY i.fecha_ingreso DESC LIMIT :lim";
        $stmtIncomes = $this->pdo->prepare($sqlIncomes);
        $stmtIncomes->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmtIncomes->execute();
        $incomes = $stmtIncomes->fetchAll(PDO::FETCH_ASSOC);

        $sqlExpenses = "SELECT e.id, 'out' as type, e.fecha_egreso as date, 'Egreso' as bankDesc, 
                               COALESCE(e.descripcion, CONCAT('Egreso - ', e.beneficiario)) as detail, 
                               e.monto as amount, e.cuenta_origen as account,
                               p.nombre as project_name
                        FROM expenses e
                        LEFT JOIN projects p ON e.proyecto_id = p.id
                        ORDER BY e.fecha_egreso DESC LIMIT :lim";
        $stmtExpenses = $this->pdo->prepare($sqlExpenses);
        $stmtExpenses->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmtExpenses->execute();
        $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

        $transactions = array_merge($incomes, $expenses);
        usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return array_slice($transactions, 0, $limit);
    }
    
    public function updateBalance(string $cuentaName, float $amountChange): void
    {
        $stmt = $this->pdo->prepare("UPDATE bank_accounts SET saldo_actual = saldo_actual + :change WHERE CONCAT(nombre_banco, ' - ', numero_cuenta) = :name OR numero_cuenta = :accNum");
        $parts = explode(' - ', $cuentaName);
        $accNum = count($parts) > 1 ? trim($parts[1]) : trim($cuentaName);
        $stmt->execute([
            'change' => $amountChange,
            'name' => $cuentaName,
            'accNum' => $accNum
        ]);
    }

    public function transfer(int $sourceId, int $destinationId, float $amount, string $date, string $description, ?string $reference = null): void
    {
        if ($sourceId === $destinationId) {
            throw new Exception("La cuenta de origen y destino no pueden ser la misma.");
        }
        if ($amount <= 0) {
            throw new Exception("El monto a transferir debe ser mayor a 0.");
        }

        $source = $this->findById($sourceId);
        $dest = $this->findById($destinationId);

        if (!$source || !$dest) {
            throw new Exception("Una de las cuentas seleccionadas no existe.");
        }

        $sourceName = $source['nombre_banco'] . ' - ' . $source['numero_cuenta'];
        $destName = $dest['nombre_banco'] . ' - ' . $dest['numero_cuenta'];

        $this->pdo->beginTransaction();
        try {
            // Deduct from source
            $stmtSource = $this->pdo->prepare("UPDATE bank_accounts SET saldo_actual = saldo_actual - :amt WHERE id = :id");
            $stmtSource->execute(['amt' => $amount, 'id' => $sourceId]);

            // Add to destination
            $stmtDest = $this->pdo->prepare("UPDATE bank_accounts SET saldo_actual = saldo_actual + :amt WHERE id = :id");
            $stmtDest->execute(['amt' => $amount, 'id' => $destinationId]);

            // Create Expense record for source account
            $expSql = "INSERT INTO expenses (proyecto_id, contratista_id, tipo_egreso, monto, fecha_egreso, cuenta_origen, numero_cheque, beneficiario, descripcion, comprobante_path)
                       VALUES (NULL, NULL, 'Transferencia', :monto, :fecha, :cuenta, :ref, :beneficiario, :desc, NULL)";
            $this->pdo->prepare($expSql)->execute([
                'monto' => $amount,
                'fecha' => $date,
                'cuenta' => $sourceName,
                'ref' => $reference,
                'beneficiario' => $dest['nombre_banco'],
                'desc' => 'Transferencia a ' . $destName . ($description ? ' (' . $description . ')' : '')
            ]);

            // Create Income record for destination account
            $incSql = "INSERT INTO incomes (proyecto_id, tipo_ingreso, monto, fecha_ingreso, cuenta_bancaria, numero_cheque, pagador, descripcion, comprobante_path)
                       VALUES (NULL, 'Transferencia', :monto, :fecha, :cuenta, :ref, :pagador, :desc, NULL)";
            $this->pdo->prepare($incSql)->execute([
                'monto' => $amount,
                'fecha' => $date,
                'cuenta' => $destName,
                'ref' => $reference,
                'pagador' => $source['nombre_banco'],
                'desc' => 'Transferencia desde ' . $sourceName . ($description ? ' (' . $description . ')' : '')
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function reconcile(int $id, float $nuevoSaldo, string $notas = ''): void
    {
        $account = $this->findById($id);
        if (!$account) throw new Exception("Cuenta no encontrada.");

        $diff = $nuevoSaldo - (float)$account['saldo_actual'];

        $this->pdo->prepare("UPDATE bank_accounts SET saldo_actual = :saldo WHERE id = :id")
             ->execute(['saldo' => $nuevoSaldo, 'id' => $id]);

        if (abs($diff) > 0.01) {
            $accName = $account['nombre_banco'] . ' - ' . $account['numero_cuenta'];
            $today = date('Y-m-d H:i:s');
            if ($diff > 0) {
                // Adjustment Income
                $this->pdo->prepare("INSERT INTO incomes (proyecto_id, tipo_ingreso, monto, fecha_ingreso, cuenta_bancaria, pagador, descripcion)
                                     VALUES (NULL, 'Ajuste Conciliación', :monto, :fecha, :cuenta, 'Ajuste Interno', :desc)")
                     ->execute([
                         'monto' => $diff,
                         'fecha' => $today,
                         'cuenta' => $accName,
                         'desc' => 'Ajuste positivo por conciliación bancaria' . ($notas ? ' - ' . $notas : '')
                     ]);
            } else {
                // Adjustment Expense
                $this->pdo->prepare("INSERT INTO expenses (proyecto_id, tipo_egreso, monto, fecha_egreso, cuenta_origen, beneficiario, descripcion)
                                     VALUES (NULL, 'Ajuste Conciliación', :monto, :fecha, :cuenta, 'Ajuste Interno', :desc)")
                     ->execute([
                         'monto' => abs($diff),
                         'fecha' => $today,
                         'cuenta' => $accName,
                         'desc' => 'Ajuste negativo por conciliación bancaria' . ($notas ? ' - ' . $notas : '')
                     ]);
            }
        }
    }
}

