<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\CreditService;
use Exception;

class CreditsController extends Controller
{
    private CreditService $creditService;

    public function __construct()
    {
        $this->creditService = new CreditService();
    }

    // Listar créditos con sus pagos
    #[Route('/credits', 'GET')]
    public function index()
    {
        try {
            $credits = $this->creditService->getAllCredits();
            $this->json(['status' => 'success', 'message' => 'Créditos obtenidos correctamente', 'data' => $credits]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al obtener créditos: ' . $e->getMessage()], 500);
        }
    }

    // Registrar nuevo crédito
    #[Route('/credits', 'POST')]
    public function store()
    {
        try {
            $data = [
                'supplier_id'    => $_POST['supplier_id'] ?? null,
                'project_id'     => $_POST['project_id'] ?? null,
                'invoice_number' => $_POST['invoice_number'] ?? '',
                'purchase_date'  => $_POST['purchase_date'] ?? date('Y-m-d'),
                'amount'         => isset($_POST['amount']) ? (float)$_POST['amount'] : 0,
                'due_date'       => $_POST['due_date'] ?? date('Y-m-d'),
                'observations'   => $_POST['observations'] ?? '',
                'status'         => 'Pendiente'
            ];

            $this->creditService->createCredit($data);

            $this->json(['status' => 'success', 'message' => 'Crédito registrado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error al registrar el crédito: ' . $e->getMessage()], $code);
        }
    }

    // Registrar nuevo abono
    #[Route('/credit-payments', 'POST')]
    public function storePayment()
    {
        try {
            $data = [
                'credit_id'       => $_POST['credit_id'] ?? null,
                'amount'          => isset($_POST['amount']) ? (float)$_POST['amount'] : 0,
                'payment_date'    => $_POST['payment_date'] ?? date('Y-m-d'),
                'bank_account_id' => $_POST['bank_account_id'] ?? null,
                'check_number'    => $_POST['check_number'] ?? '',
            ];

            $fileData = $_FILES['receipt'] ?? null;

            $this->creditService->createPayment($data, $fileData);

            $this->json(['status' => 'success', 'message' => 'Abono registrado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error al registrar el abono: ' . $e->getMessage()], $code);
        }
    }
}
