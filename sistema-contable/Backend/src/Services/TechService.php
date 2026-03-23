<?php
namespace App\Services;

use App\Repositories\AssetRepository;

class TechService
{
    private $assetRepo;

    public function __construct()
    {
        $this->assetRepo = new AssetRepository();
    }

    public function getDashboardData()
    {
        $recentActivity = $this->assetRepo->findRecentActivity(10);
        $assets = $this->assetRepo->findAllAssets();

        return [
            'recentActivity' => $recentActivity,
            'assets' => $assets
        ];
    }

    public function createAssetTransaction($data, $userId)
    {
        $data['created_by'] = $userId;
        return $this->assetRepo->createTransaction($data);
    }
}
