<?php
namespace App\Services;

use App\Repositories\PaymentRepository;

class PaymentService
{
    private $paymentRepo;

    public function __construct()
    {
        $this->paymentRepo = new PaymentRepository();
    }

    public function getAllPayments()
    {
        return $this->paymentRepo->findAll();
    }

    public function getTotalDebt()
    {
        return $this->paymentRepo->getTotalDebt();
    }

    public function getPendingInvoices()
    {
        return $this->paymentRepo->getPendingInvoices();
    }

    public function createPayment($data)
    {
        if (empty($data['factura_id']) || empty($data['fecha_pago']) || empty($data['banco']) || empty($data['monto_pago']) || empty($data['usuario_id'])) {
            throw new \Exception("Todos los campos obligatorios deben proporcionarse");
        }
        return $this->paymentRepo->create($data);
    }
}
