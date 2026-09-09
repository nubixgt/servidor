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
                'saldo_inicial' => isset($data['saldo_inicial']) ? (float)$data['saldo_inicial'] : 0,
                'activa'        => isset($data['activa']) ? (int)$data['activa'] : 1,
            ];

            $newId = $this->bankConciliationService->createAccount($accountData);

            $this->json(['status' => 'success', 'message' => 'Cuenta bancaria registrada correctamente.', 'data' => ['id' => $newId]]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // PUT /bank-accounts/{id}
    // ----------------------------------------------------------------
    #[Route('/bank-accounts/(\d+)', 'PUT')]
    public function updateAccount(int $id)
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
                'saldo_actual'  => isset($data['saldo_actual']) ? (float)$data['saldo_actual'] : (isset($data['saldo_inicial']) ? (float)$data['saldo_inicial'] : 0),
                'saldo_inicial' => isset($data['saldo_inicial']) ? (float)$data['saldo_inicial'] : (isset($data['saldo_actual']) ? (float)$data['saldo_actual'] : 0),
            ];

            $this->bankConciliationService->updateAccount($id, $accountData);

            $this->json(['status' => 'success', 'message' => 'Cuenta bancaria actualizada correctamente.']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /bank-accounts/{id}
    // ----------------------------------------------------------------
    #[Route('/bank-accounts/(\d+)', 'DELETE')]
    public function deleteAccount(int $id)
    {
        try {
            $this->bankConciliationService->deleteAccount($id);
            $this->json(['status' => 'success', 'message' => 'Cuenta bancaria eliminada correctamente.']);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // GET /bank-accounts/all-transactions
    // ----------------------------------------------------------------
    #[Route('/bank-accounts/all-transactions', 'GET')]
    public function getAllTransactions()
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $transactions = $this->bankConciliationService->getAllTransactions($limit);
            $this->json(['status' => 'success', 'data' => $transactions]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // GET /bank-accounts/{id}/history
    // ----------------------------------------------------------------
    #[Route('/bank-accounts/(\d+)/history', 'GET')]
    public function getHistory(int $id)
    {
        try {
            $transactions = $this->bankConciliationService->getAccountHistory($id);
            $this->json(['status' => 'success', 'data' => $transactions]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /bank-accounts/transfer
    // ----------------------------------------------------------------
    #[Route('/bank-accounts/transfer', 'POST')]
    public function transfer()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $sourceId = (int)($data['cuenta_origen_id'] ?? 0);
            $destId   = (int)($data['cuenta_destino_id'] ?? 0);
            $amount   = (float)($data['monto'] ?? 0);
            $date     = trim($data['fecha'] ?? date('Y-m-d'));
            $desc     = trim($data['descripcion'] ?? 'Transferencia entre cuentas');
            $ref      = !empty($data['referencia']) ? trim($data['referencia']) : null;

            if ($sourceId <= 0 || $destId <= 0) {
                throw new Exception("Selecciona cuentas válidas de origen y destino.");
            }
            if ($amount <= 0) {
                throw new Exception("El monto a transferir debe ser mayor a 0.");
            }

            $this->bankConciliationService->transfer($sourceId, $destId, $amount, $date, $desc, $ref);

            $this->json(['status' => 'success', 'message' => 'Transferencia realizada exitosamente.']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /bank-accounts/{id}/reconcile
    // ----------------------------------------------------------------
    #[Route('/bank-accounts/(\d+)/reconcile', 'POST')]
    public function reconcile(int $id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $nuevoSaldo = (float)($data['nuevo_saldo'] ?? 0);
            $notas = trim($data['notas'] ?? '');

            $this->bankConciliationService->reconcile($id, $nuevoSaldo, $notas);

            $this->json(['status' => 'success', 'message' => 'Conciliación bancaria aplicada correctamente.']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}

