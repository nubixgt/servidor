<?php
namespace App\Services;

use App\Repositories\ReportRepository;

class ReportService {
    private $repository;

    public function __construct(ReportRepository $repository) {
        $this->repository = $repository;
    }

    public function getDashboardData() {
        $stats = $this->repository->getDashboardStats();
        $inversionistas = $this->repository->getCapitalPorInversionista();
        
        return [
            'stats' => $stats,
            'charts' => [
                'inversionistas' => $inversionistas
            ]
        ];
    }

    public function getClienteReport(int $cliente_id) {
        return $this->repository->getClienteEstadoCuenta($cliente_id);
    }
}
