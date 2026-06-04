<?php
namespace App\Services;

use App\Repositories\IncomeRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\BankAccountRepository;
use Exception;

class FinanceService
{
    private IncomeRepository $incomeRepository;
    private ExpenseRepository $expenseRepository;
    private BankAccountRepository $bankAccountRepository;

    public function __construct()
    {
        $this->incomeRepository = new IncomeRepository();
        $this->expenseRepository = new ExpenseRepository();
        $this->bankAccountRepository = new BankAccountRepository();
    }

    public function getTransactionsData(): array
    {
        $incomes = $this->incomeRepository->findAllWithProjects();
        $expenses = $this->expenseRepository->findAllWithProjects();

        $transactions = array_merge($incomes, $expenses);
        usort($transactions, function($a, $b) {
            $dateA = strtotime($a['fecha_ingreso'] ?? $a['fecha_egreso']);
            $dateB = strtotime($b['fecha_ingreso'] ?? $b['fecha_egreso']);
            return $dateB - $dateA; // Descending
        });

        $totalIncome = array_reduce($incomes, fn($c, $i) => $c + (float)$i['monto'], 0);
        $totalExpense = array_reduce($expenses, fn($c, $e) => $c + (float)$e['monto'], 0);
        $net = $totalIncome - $totalExpense;

        return [
            'transactions' => $transactions,
            'kpis' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $net
            ]
        ];
    }

    public function createIncome(array $data, ?array $fileData): void
    {
        if (empty($data['tipo_ingreso']) || empty($data['monto']) || empty($data['fecha_ingreso']) || empty($data['cuenta_bancaria'])) {
            throw new Exception('Faltan campos obligatorios para el ingreso.', 400);
        }

        $pdo = $this->incomeRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->incomeRepository->create($data);
            
            $cuentaName = trim($data['cuenta_bancaria'] ?? '');
            if ($cuentaName) {
                $this->bankAccountRepository->updateBalance($cuentaName, (float)$data['monto']);
            }

            if (!empty($data['registros']) && is_array($data['registros'])) {
                foreach ($data['registros'] as $record) {
                    $desc = trim($record['descripcion'] ?? '');
                    $amt = (float)($record['monto'] ?? 0);
                    if ($desc !== '' || $amt > 0) {
                        $this->incomeRepository->addRecord($newId, $desc, $amt);
                    }
                }
            }

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Incomes/' . $newId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($fileData['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileData['tmp_name'], $targetFile)) {
                    $comprobante_path = 'Uploads/Incomes/' . $newId . '/' . $fileName;
                    $this->incomeRepository->updateComprobante($newId, $comprobante_path);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function createExpense(array $data, ?array $fileData): void
    {
        if (empty($data['tipo_egreso']) || empty($data['monto']) || empty($data['fecha_egreso']) || empty($data['beneficiario'])) {
            throw new Exception('Faltan campos obligatorios para el egreso.', 400);
        }

        $pdo = $this->expenseRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->expenseRepository->create($data);
            
            $cuentaName = trim($data['cuenta_origen'] ?? '');
            if ($cuentaName) {
                $this->bankAccountRepository->updateBalance($cuentaName, -abs((float)$data['monto']));
            }

            if (!empty($data['registros']) && is_array($data['registros'])) {
                foreach ($data['registros'] as $record) {
                    $desc = trim($record['descripcion'] ?? '');
                    $amt = (float)($record['monto'] ?? 0);
                    if ($desc !== '' || $amt > 0) {
                        $this->expenseRepository->addRecord($newId, $desc, $amt);
                    }
                }
            }

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../Uploads/Expenses/' . $newId . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($fileData['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileData['tmp_name'], $targetFile)) {
                    $comprobante_path = 'Uploads/Expenses/' . $newId . '/' . $fileName;
                    $this->expenseRepository->updateComprobante($newId, $comprobante_path);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
