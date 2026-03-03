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

    public function getSaleWithDetails($id)
    {
        return $this->saleRepo->findByIdWithDetails($id);
    }

    public function deleteSale($id)
    {
        return $this->saleRepo->deleteTransaction($id);
    }

    public function createSale($data)
    {
        // Basic validations
        if (empty($data['cliente_id']) || empty($data['vendedor']) || empty($data['productos'])) {
            throw new \Exception("Datos incompletos para procesar la venta.");
        }

        // Generate Nota Envio number if not passed (Server-side generated is better)
        if (empty($data['numero_nota'])) {
            // Usually this involves querying the last number and incrementing
            $data['numero_nota'] = $this->saleRepo->generateNextNotaNumber();
        }

        if (empty($data['fecha'])) {
            $data['fecha'] = date('Y-m-d H:i:s');
        }

        $id = $this->saleRepo->createTransaction($data);
        return ['id' => $id, 'numero_nota' => $data['numero_nota']];
    }
}
