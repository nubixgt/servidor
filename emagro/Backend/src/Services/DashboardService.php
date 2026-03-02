<?php
namespace App\Services;

use App\Repositories\SaleRepository;
use App\Repositories\ClientRepository;
use App\Repositories\ProductRepository;

class DashboardService
{
    private $saleRepo;
    private $clientRepo;
    private $productRepo;

    public function __construct()
    {
        $this->saleRepo = new SaleRepository();
        $this->clientRepo = new ClientRepository();
        $this->productRepo = new ProductRepository();
    }

    public function getDashboardKPIs()
    {
        $sales = $this->saleRepo->findAll();
        $clients = $this->clientRepo->findAll();
        $products = $this->productRepo->findAll();

        $ventasTotales = $this->saleRepo->getTotalSalesAmount();
        $clientesActivos = count($clients);
        $stockBajo = count(array_filter($products, fn($p) => $p->stock_status === 'Agotado')); // O lógica más compleja

        $promedioVenta = count($sales) > 0 ? $ventasTotales / count($sales) : 0;

        return [
            'ventas_totales' => $ventasTotales,
            'clientes_activos' => $clientesActivos,
            'stock_bajo' => $stockBajo,
            'promedio_venta' => $promedioVenta,
            'ventas_por_categoria' => $this->saleRepo->getSalesByCategory(),
            'ventas_recientes' => $this->saleRepo->getRecentSales(5)
        ];
    }
}
