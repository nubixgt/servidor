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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $data = [
                'supplier_id'    => !empty($input['supplier_id']) ? (int)$input['supplier_id'] : null,
                'project_id'     => !empty($input['project_id']) ? (int)$input['project_id'] : null,
                'invoice_number' => trim($input['invoice_number'] ?? ''),
                'purchase_date'  => trim($input['purchase_date'] ?? date('Y-m-d')),
                'amount'         => isset($input['amount']) ? (float)$input['amount'] : 0.0,
                'due_date'       => trim($input['due_date'] ?? date('Y-m-d')),
                'observations'   => trim($input['observations'] ?? ''),
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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $data = [
                'credit_id'       => !empty($input['credit_id']) ? (int)$input['credit_id'] : null,
                'amount'          => isset($input['amount']) ? (float)$input['amount'] : 0.0,
                'payment_date'    => trim($input['payment_date'] ?? date('Y-m-d')),
                'bank_account_id' => !empty($input['bank_account_id']) ? (int)$input['bank_account_id'] : null,
                'check_number'    => trim($input['check_number'] ?? ''),
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
