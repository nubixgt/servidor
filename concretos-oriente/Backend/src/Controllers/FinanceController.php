<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\FinanceService;
use Exception;

class FinanceController extends Controller
{
    private FinanceService $financeService;

    public function __construct()
    {
        $this->financeService = new FinanceService();
    }

    // ----------------------------------------------------------------
    // GET /finance/transactions
    // ----------------------------------------------------------------
    #[Route('/finance/transactions', 'GET')]
    public function getTransactions()
    {
        try {
            $data = $this->financeService->getTransactionsData();

            $this->json([
                'status' => 'success', 
                'data' => $data['transactions'],
                'kpis' => $data['kpis']
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
            $tipo_ingreso = trim($_POST['tipo_ingreso'] ?? '');
            if ($tipo_ingreso === 'Otro' && !empty($_POST['tipo_ingreso_otro'])) {
                $tipo_ingreso = trim($_POST['tipo_ingreso_otro']);
            }

            $data = [
                'proyecto_id'     => isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '' ? (int)$_POST['proyecto_id'] : null,
                'tipo_ingreso'    => $tipo_ingreso,
                'monto'           => isset($_POST['monto']) ? (float)$_POST['monto'] : 0,
                'fecha_ingreso'   => trim($_POST['fecha_ingreso'] ?? ''),
                'cuenta_bancaria' => trim($_POST['cuenta_bancaria'] ?? ''),
                'numero_cheque'   => trim($_POST['numero_cheque'] ?? '') ?: null,
                'pagador'         => trim($_POST['pagador'] ?? '') ?: null,
                'descripcion'     => trim($_POST['descripcion'] ?? '') ?: null,
                'registros'       => isset($_POST['registros']) ? json_decode($_POST['registros'], true) : []
            ];

            $fileData = $_FILES['comprobante'] ?? null;

            $this->financeService->createIncome($data, $fileData);

            $this->json(['status' => 'success', 'message' => 'Ingreso registrado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /finance/expenses
    // ----------------------------------------------------------------
    #[Route('/finance/expenses', 'POST')]
    public function storeExpense()
    {
        try {
            $data = [
                'proyecto_id'   => isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '' ? (int)$_POST['proyecto_id'] : null,
                'tipo_egreso'   => trim($_POST['tipo_egreso'] ?? ''),
                'monto'         => isset($_POST['monto']) ? (float)$_POST['monto'] : 0,
                'fecha_egreso'  => trim($_POST['fecha_egreso'] ?? ''),
                'cuenta_origen' => trim($_POST['cuenta_origen'] ?? '') ?: null,
                'numero_cheque' => trim($_POST['numero_cheque'] ?? '') ?: null,
                'beneficiario'  => trim($_POST['beneficiario'] ?? ''),
                'descripcion'   => trim($_POST['descripcion'] ?? '') ?: null,
                'registros'     => isset($_POST['registros']) ? json_decode($_POST['registros'], true) : []
            ];

            $fileData = $_FILES['comprobante'] ?? null;

            $this->financeService->createExpense($data, $fileData);

            $this->json(['status' => 'success', 'message' => 'Egreso registrado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
