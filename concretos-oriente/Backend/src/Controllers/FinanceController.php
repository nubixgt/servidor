<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use Exception;

class FinanceController extends Controller
{
    // ----------------------------------------------------------------
    // GET /finance/transactions
    // ----------------------------------------------------------------
    #[Route('/finance/transactions', 'GET')]
    public function getTransactions()
    {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Fetch Incomes
            $sqlIncomes = "SELECT i.*, p.nombre as proyecto_nombre, 'Ingreso' as transaction_type 
                           FROM incomes i 
                           LEFT JOIN projects p ON i.proyecto_id = p.id";
            $stmtIncomes = $pdo->query($sqlIncomes);
            $incomes = $stmtIncomes->fetchAll();

            // Fetch Expenses
            $sqlExpenses = "SELECT e.*, p.nombre as proyecto_nombre, 'Egreso' as transaction_type 
                            FROM expenses e 
                            LEFT JOIN projects p ON e.proyecto_id = p.id";
            $stmtExpenses = $pdo->query($sqlExpenses);
            $expenses = $stmtExpenses->fetchAll();

            // Merge and sort by date descending
            $transactions = array_merge($incomes, $expenses);
            usort($transactions, function($a, $b) {
                $dateA = strtotime($a['fecha_ingreso'] ?? $a['fecha_egreso']);
                $dateB = strtotime($b['fecha_ingreso'] ?? $b['fecha_egreso']);
                return $dateB - $dateA; // Descending
            });

            // Calculate KPIs
            $totalIncome = array_reduce($incomes, fn($c, $i) => $c + (float)$i['monto'], 0);
            $totalExpense = array_reduce($expenses, fn($c, $e) => $c + (float)$e['monto'], 0);
            $net = $totalIncome - $totalExpense;

            $kpis = [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $net
            ];

            $this->json([
                'status' => 'success', 
                'data' => $transactions,
                'kpis' => $kpis
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /finance/incomes
    // ----------------------------------------------------------------
    #[Route('/finance/incomes', 'POST')]
    public function storeIncome()
    {
        try {
            $proyecto_id      = isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '' ? (int)$_POST['proyecto_id'] : null;
            $tipo_ingreso     = trim($_POST['tipo_ingreso'] ?? '');
            if ($tipo_ingreso === 'Otro' && !empty($_POST['tipo_ingreso_otro'])) {
                $tipo_ingreso = trim($_POST['tipo_ingreso_otro']);
            }
            $monto            = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
            $fecha_ingreso    = trim($_POST['fecha_ingreso'] ?? '');
            $cuenta_bancaria  = trim($_POST['cuenta_bancaria'] ?? '');
            $numero_cheque    = trim($_POST['numero_cheque'] ?? '') ?: null;
            $pagador          = trim($_POST['pagador'] ?? '') ?: null;
            $descripcion      = trim($_POST['descripcion'] ?? '') ?: null;

            if (empty($tipo_ingreso) || empty($monto) || empty($fecha_ingreso) || empty($cuenta_bancaria)) {
                $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios para el ingreso.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            
            $sql = "INSERT INTO incomes 
                        (proyecto_id, tipo_ingreso, monto, fecha_ingreso, cuenta_bancaria, numero_cheque, pagador, descripcion, comprobante_path)
                    VALUES 
                        (:proyecto_id, :tipo_ingreso, :monto, :fecha_ingreso, :cuenta_bancaria, :numero_cheque, :pagador, :descripcion, NULL)";
            
            $pdo->prepare($sql)->execute([
                'proyecto_id'     => $proyecto_id,
                'tipo_ingreso'    => $tipo_ingreso,
                'monto'           => $monto,
                'fecha_ingreso'   => $fecha_ingreso,
                'cuenta_bancaria' => $cuenta_bancaria,
                'numero_cheque'   => $numero_cheque,
                'pagador'         => $pagador,
                'descripcion'     => $descripcion
            ]);

            $newId = $pdo->lastInsertId();
            $comprobante_path = null;

            if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Incomes/' . $newId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['comprobante']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $targetFile)) {
                    $comprobante_path = 'Uploads/Incomes/' . $newId . '/' . $fileName;
                    $pdo->prepare("UPDATE incomes SET comprobante_path = :comp WHERE id = :id")
                        ->execute(['comp' => $comprobante_path, 'id' => $newId]);
                }
            }

            $this->json(['status' => 'success', 'message' => 'Ingreso registrado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /finance/expenses
    // ----------------------------------------------------------------
    #[Route('/finance/expenses', 'POST')]
    public function storeExpense()
    {
        try {
            $proyecto_id      = isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '' ? (int)$_POST['proyecto_id'] : null;
            $tipo_egreso      = trim($_POST['tipo_egreso'] ?? '');
            $monto            = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
            $fecha_egreso     = trim($_POST['fecha_egreso'] ?? '');
            $cuenta_origen    = trim($_POST['cuenta_origen'] ?? '') ?: null;
            $numero_cheque    = trim($_POST['numero_cheque'] ?? '') ?: null;
            $beneficiario     = trim($_POST['beneficiario'] ?? '');
            $descripcion      = trim($_POST['descripcion'] ?? '') ?: null;

            if (empty($tipo_egreso) || empty($monto) || empty($fecha_egreso) || empty($beneficiario)) {
                $this->json(['status' => 'error', 'message' => 'Faltan campos obligatorios para el egreso.'], 400);
                return;
            }

            $pdo = Database::getInstance()->getConnection();
            
            $sql = "INSERT INTO expenses 
                        (proyecto_id, tipo_egreso, monto, fecha_egreso, cuenta_origen, numero_cheque, beneficiario, descripcion, comprobante_path)
                    VALUES 
                        (:proyecto_id, :tipo_egreso, :monto, :fecha_egreso, :cuenta_origen, :numero_cheque, :beneficiario, :descripcion, NULL)";
            
            $pdo->prepare($sql)->execute([
                'proyecto_id'     => $proyecto_id,
                'tipo_egreso'     => $tipo_egreso,
                'monto'           => $monto,
                'fecha_egreso'    => $fecha_egreso,
                'cuenta_origen'   => $cuenta_origen,
                'numero_cheque'   => $numero_cheque,
                'beneficiario'    => $beneficiario,
                'descripcion'     => $descripcion
            ]);

            $newId = $pdo->lastInsertId();
            $comprobante_path = null;

            if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Expenses/' . $newId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['comprobante']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $targetFile)) {
                    $comprobante_path = 'Uploads/Expenses/' . $newId . '/' . $fileName;
                    $pdo->prepare("UPDATE expenses SET comprobante_path = :comp WHERE id = :id")
                        ->execute(['comp' => $comprobante_path, 'id' => $newId]);
                }
            }

            $this->json(['status' => 'success', 'message' => 'Egreso registrado correctamente']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
