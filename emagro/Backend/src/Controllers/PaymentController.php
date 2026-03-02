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
    #[Authorize(['Administrador', 'Vendedor'])]
    public function index()
    {
        $service = new PaymentService();
        $payments = $service->getAllPayments();
        Response::success($payments);
    }
}
