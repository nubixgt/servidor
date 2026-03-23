<?php
namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\LocationRepository;
use App\Repositories\UserRepository;

class AdminService
{
    private $transactionRepo;
    private $locationRepo;
    private $userRepo;

    public function __construct()
    {
        $this->transactionRepo = new TransactionRepository();
        $this->locationRepo = new LocationRepository();
        $this->userRepo = new UserRepository();
    }

    public function getDashboardData()
    {
        $kpis = $this->transactionRepo->getKPIs();
        $recentTransactions = $this->transactionRepo->findRecent(5);
        
        // Mocking alerts for the dashboard based on the frontend
        $alerts = [
            ['id' => 1, 'message' => 'Contrato Local L-01 por vencer en 15 días', 'type' => 'warning'],
            ['id' => 2, 'message' => '5 ingresos pendientes de revisión', 'type' => 'info']
        ];

        return [
            'kpis' => [
                'total_ingresos' => $kpis['total_ingresos'] ?? 0,
                'total_egresos' => $kpis['total_egresos'] ?? 0,
                'balance_neto' => ($kpis['total_ingresos'] ?? 0) - ($kpis['total_egresos'] ?? 0)
            ],
            'recentTransactions' => $recentTransactions,
            'alerts' => $alerts
        ];
    }

    public function getLocations()
    {
        return $this->locationRepo->findAll();
    }

    public function getReports()
    {
        return $this->transactionRepo->findAll();
    }

    public function getUsers()
    {
        return $this->userRepo->findAll();
    }

    public function createTransaction($data, $userId)
    {
        $data['created_by'] = $userId;
        return $this->transactionRepo->create($data);
    }

    public function createLocation($data)
    {
        return $this->locationRepo->create($data);
    }
    
    public function updateLocation($id, $data)
    {
        return $this->locationRepo->update($id, $data);
    }
    
    public function deleteLocation($id)
    {
        return $this->locationRepo->delete($id);
    }

    public function getLocationById($id)
    {
        return $this->locationRepo->findById($id);
    }
}
