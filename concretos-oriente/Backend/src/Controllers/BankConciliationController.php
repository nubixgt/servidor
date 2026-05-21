<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class BankConciliationController extends Controller
{
    // ----------------------------------------------------------------
    // GET /bank-accounts
    // ----------------------------------------------------------------
    #[Route('/bank-accounts', 'GET')]
    public function getAccounts()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("SELECT * FROM bank_accounts ORDER BY id DESC");
            $accounts = $stmt->fetchAll();

            $this->json(['status' => 'success', 'data' => $accounts]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /bank-accounts
    // ----------------------------------------------------------------
    #[Route('/bank-accounts', 'POST')]
    public function storeAccount()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $nombre_banco  = trim($data['nombre_banco'] ?? '');
            $numero_cuenta = trim($data['numero_cuenta'] ?? '');
            $tipo_cuenta   = trim($data['tipo_cuenta'] ?? '');
            $moneda        = trim($data['moneda'] ?? 'GTQ');
            $activa        = isset($data['activa']) ? (int)$data['activa'] : 1;

            if (empty($nombre_banco) || empty($numero_cuenta) || empty($tipo_cuenta)) {
                $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios para registrar la cuenta.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            $sql = "INSERT INTO bank_accounts (nombre_banco, numero_cuenta, tipo_cuenta, moneda, activa)
                    VALUES (:nombre_banco, :numero_cuenta, :tipo_cuenta, :moneda, :activa)";
            
            $pdo->prepare($sql)->execute([
                'nombre_banco'  => $nombre_banco,
                'numero_cuenta' => $numero_cuenta,
                'tipo_cuenta'   => $tipo_cuenta,
                'moneda'        => $moneda,
                'activa'        => $activa
            ]);

            $this->json(['status' => 'success', 'message' => 'Cuenta bancaria registrada correctamente.']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // GET /bank-transactions (Partidas conciliatorias)
    // ----------------------------------------------------------------
    #[Route('/bank-transactions', 'GET')]
    public function getPendingTransactions()
    {
        try {
            // Ideally this filters by bank_account_name or ID
            $cuenta = $_GET['cuenta'] ?? '';
            
            $pdo = Database::getInstance()->getConnection();
            
            // Incomes
            $sqlIncomes = "SELECT id, 'in' as type, fecha_ingreso as date, 'INGRESO' as bankDesc, descripcion as detail, monto as amount, cuenta_bancaria as account
                           FROM incomes";
            if ($cuenta) $sqlIncomes .= " WHERE cuenta_bancaria LIKE :cuenta";

            $stmtIncomes = $pdo->prepare($sqlIncomes);
            if ($cuenta) $stmtIncomes->execute(['cuenta' => "%$cuenta%"]);
            else $stmtIncomes->execute();
            $incomes = $stmtIncomes->fetchAll();

            // Expenses
            $sqlExpenses = "SELECT id, 'out' as type, fecha_egreso as date, 'EGRESO' as bankDesc, descripcion as detail, monto as amount, cuenta_origen as account
                            FROM expenses";
            if ($cuenta) $sqlExpenses .= " WHERE cuenta_origen LIKE :cuenta";

            $stmtExpenses = $pdo->prepare($sqlExpenses);
            if ($cuenta) $stmtExpenses->execute(['cuenta' => "%$cuenta%"]);
            else $stmtExpenses->execute();
            $expenses = $stmtExpenses->fetchAll();

            $transactions = array_merge($incomes, $expenses);
            usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

            // Format for frontend
            $formatted = array_map(function($tx) {
                return [
                    'id' => $tx['type'] . '-' . $tx['id'],
                    'date' => $tx['date'],
                    'bankDesc' => $tx['bankDesc'] . ' - ' . $tx['account'],
                    'detail' => $tx['detail'] ?: 'Sin detalle',
                    'refSystem' => 'Ref DB: ' . $tx['id'],
                    'isLinked' => true,
                    'amount' => (float)$tx['amount'],
                    'type' => $tx['type'],
                    'status' => 'pending' // Asumimos pendientes
                ];
            }, $transactions);

            $this->json(['status' => 'success', 'data' => $formatted]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /bank-reconciliations
    // ----------------------------------------------------------------
    #[Route('/bank-reconciliations', 'POST')]
    public function storeReconciliation()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $bank_account_id = isset($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
            $periodo         = trim($data['periodo'] ?? '');
            $saldo_banco     = isset($data['saldo_banco']) ? (float)$data['saldo_banco'] : 0.0;
            // JSON array of reconciled transaction IDs
            $partidas        = isset($data['partidas_conciliatorias']) ? json_encode($data['partidas_conciliatorias']) : '[]';

            if (!$bank_account_id || empty($periodo)) {
                $this->json(['status' => 'error', 'message' => 'Cuenta bancaria y período son obligatorios.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            $sql = "INSERT INTO bank_reconciliations (bank_account_id, periodo, saldo_banco, partidas_conciliatorias)
                    VALUES (:bank_account_id, :periodo, :saldo_banco, :partidas_conciliatorias)";
            
            $pdo->prepare($sql)->execute([
                'bank_account_id'         => $bank_account_id,
                'periodo'                 => $periodo,
                'saldo_banco'             => $saldo_banco,
                'partidas_conciliatorias' => $partidas
            ]);

            $this->json(['status' => 'success', 'message' => 'Conciliación registrada correctamente.']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
