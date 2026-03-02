<?php
namespace App\Services;

use App\Repositories\SaleRepository;

class SaleService
{
    private $saleRepo;

    public function __construct()
    {
        $this->saleRepo = new SaleRepository();
    }

    public function getAllSales()
    {
        return $this->saleRepo->findAll();
    }
}
