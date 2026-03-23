<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Services\TechService;
use App\Utils\JwtUtils;

class TechController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new TechService();
    }

    private function getUserId()
    {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        $payload = JwtUtils::validate($token);
        return $payload ? $payload['id'] : null;
    }

    #[Route('/tech/dashboard', 'GET')]
    #[Authorize(['tech', 'admin'])]
    #[HasPrivilege('view_dashboard_tech')]
    public function getDashboard()
    {
        $data = $this->service->getDashboardData();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/assets/transactions', 'POST')]
    #[Authorize(['tech', 'admin'])]
    #[HasPrivilege('create_asset_transaction')]
    public function createAssetTransaction()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = $this->getUserId();

        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
        }

        try {
            $id = $this->service->createAssetTransaction($data, $userId);
            $this->json(['status' => 'success', 'message' => 'Movimiento registrado', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
