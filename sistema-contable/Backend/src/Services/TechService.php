<?php
namespace App\Services;

use App\Repositories\TransactionRepository;

class TechService
{
    private $transactionRepo;

    public function __construct()
    {
        $this->transactionRepo = new TransactionRepository();
    }

    public function getDashboardData($userId)
    {
        $recentActivity = $this->transactionRepo->findTodayRecentByUser(10, $userId);
        $assets = [];

        return [
            'recentActivity' => $recentActivity,
            'assets' => $assets
        ];
    }

    public function getHistoryData($userId)
    {
        $transactions = $this->transactionRepo->findAllTodayByUser($userId);
        $kpis = $this->transactionRepo->getTodayTechKPIs($userId);
        
        return [
            'transactions' => $transactions,
            'kpis' => $kpis
        ];
    }
}
