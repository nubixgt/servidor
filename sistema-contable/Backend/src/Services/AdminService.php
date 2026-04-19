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
        $monthlyData = $this->transactionRepo->getMonthlyData(6);
        $salesByLocation = $this->transactionRepo->getSalesByLocation();

        $alerts = [];

        // Alert 1: Egresos > 80% of Ingresos this month
        $ingresos = floatval($kpis['total_ingresos'] ?? 0);
        $egresos = floatval($kpis['total_egresos'] ?? 0);
        if ($ingresos > 0 && $egresos > 0 && ($egresos / $ingresos) >= 0.8) {
            $pct = round(($egresos / $ingresos) * 100);
            $alerts[] = [
                'id' => uniqid(),
                'message' => "Los egresos representan el {$pct}% de los ingresos totales",
                'type' => 'warning'
            ];
        }

        // Alert 2: Locations with no reports in last 24 hours
        $inactive24h = $this->transactionRepo->getInactiveLocationsLast24h();
        foreach ($inactive24h as $loc) {
            $alerts[] = [
                'id' => uniqid(),
                'message' => "El local '{$loc['name']}' no ha reportado actividad en las últimas 24 horas",
                'type' => 'error'
            ];
        }

        return [
            'kpis' => [
                'total_ingresos' => $kpis['total_ingresos'] ?? 0,
                'total_egresos'  => $kpis['total_egresos'] ?? 0,
                'balance_neto'   => ($kpis['total_ingresos'] ?? 0) - ($kpis['total_egresos'] ?? 0)
            ],
            'monthlyData' => $monthlyData,
            'salesByLocation' => $salesByLocation,
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

    public function getAllTransactions()
    {
        return $this->transactionRepo->findAll();
    }

    public function getTransactionById($id)
    {
        return $this->transactionRepo->findById($id);
    }

    public function updateTransaction($id, $data)
    {
        return $this->transactionRepo->update($id, $data);
    }

    public function deleteTransaction($id)
    {
        return $this->transactionRepo->delete($id);
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

    // --- USERS ---

    public function createUser($data)
    {
        return $this->userRepo->create($data);
    }
    
    public function updateUser($id, $data)
    {
        return $this->userRepo->update($id, $data);
    }
    
    public function deleteUser($id)
    {
        return $this->userRepo->delete($id);
    }

    public function getUserById($id)
    {
        return $this->userRepo->findById($id);
    }
}
