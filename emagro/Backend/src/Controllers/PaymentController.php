<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\PaymentService;
use App\Utils\Response;

class PaymentController extends Controller
{
    #[Route('/payments', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    public function index()
    {
        $service = new PaymentService();
        $payments = $service->getAllPayments();
        $deudaTotal = $service->getTotalDebt();

        Response::success([
            'data' => $payments,
            'meta' => [
                'deuda_total' => $deudaTotal
            ]
        ]);
    }

    #[Route('/payments/pending', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    public function pending()
    {
        $service = new PaymentService();
        $invoices = $service->getPendingInvoices();
        Response::success($invoices);
    }

    #[Route('/payments', 'POST')]
    #[Authorize(['admin', 'vendedor'])]
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['usuario_id'])) {
            $data['usuario_id'] = 1; // Fallback
        }

        try {
            $service = new PaymentService();
            $paymentId = $service->createPayment($data);
            Response::success(['id' => $paymentId], "Pago registrado con éxito", 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
