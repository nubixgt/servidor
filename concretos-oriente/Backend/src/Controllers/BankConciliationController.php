<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\BankConciliationService;
use Exception;

class BankConciliationController extends Controller
{
    private BankConciliationService $bankConciliationService;

    public function __construct()
    {
        $this->bankConciliationService = new BankConciliationService();
    }

    // ----------------------------------------------------------------
    // GET /bank-accounts
    // ----------------------------------------------------------------
    #[Route('/bank-accounts', 'GET')]
    public function getAccounts()
    {
        try {
            $accounts = $this->bankConciliationService->getAllAccounts();
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

            $accountData = [
                'nombre_banco'  => trim($data['nombre_banco'] ?? ''),
                'numero_cuenta' => trim($data['numero_cuenta'] ?? ''),
                'tipo_cuenta'   => trim($data['tipo_cuenta'] ?? ''),
                'moneda'        => trim($data['moneda'] ?? 'GTQ'),
                'activa'        => isset($data['activa']) ? (int)$data['activa'] : 1,
            ];

            $this->bankConciliationService->createAccount($accountData);

            $this->json(['status' => 'success', 'message' => 'Cuenta bancaria registrada correctamente.']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /bank-transactions (Partidas conciliatorias)
    // ----------------------------------------------------------------
    #[Route('/bank-transactions', 'GET')]
    public function getPendingTransactions()
    {
        try {
            $cuenta = $_GET['cuenta'] ?? '';
            
            $formatted = $this->bankConciliationService->getPendingTransactions($cuenta);

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

            $reconciliationData = [
                'bank_account_id'         => isset($data['bank_account_id']) ? (int)$data['bank_account_id'] : null,
                'periodo'                 => trim($data['periodo'] ?? ''),
                'saldo_banco'             => isset($data['saldo_banco']) ? (float)$data['saldo_banco'] : 0.0,
                'partidas_conciliatorias' => $data['partidas_conciliatorias'] ?? [],
            ];

            $this->bankConciliationService->createReconciliation($reconciliationData);

            $this->json(['status' => 'success', 'message' => 'Conciliación registrada correctamente.']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
