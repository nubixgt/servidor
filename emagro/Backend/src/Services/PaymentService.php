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
}
