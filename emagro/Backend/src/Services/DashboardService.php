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

        $paymentRepo = new \App\Repositories\PaymentRepository();

        $ventasMes = $this->saleRepo->getSalesLast30Days();
        $cobrosMes = $paymentRepo->getCollectionsLast30Days();
        $deudaTotal = $paymentRepo->getTotalDebt();

        $clientesActivos = count(array_filter($clients, fn($c) => $c->bloquear_ventas == 0));

        return [
            'ventas_mes' => $ventasMes,
            'cobros_mes' => $cobrosMes,
            'deuda_total' => $deudaTotal,
            'clientes_activos' => $clientesActivos,
            'ventas_por_categoria' => $this->saleRepo->getSalesByCategory(),
            'ventas_recientes' => $this->saleRepo->getRecentSales(5),
            'pagos_recientes' => $paymentRepo->getRecentPayments(5),
            'tendencia_ventas' => $this->saleRepo->getSalesTrend6Months(),
            'tendencia_cobros' => $paymentRepo->getPaymentsTrend6Months()
        ];
    }
}
