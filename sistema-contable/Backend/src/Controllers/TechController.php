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
        $userId = $this->getUserId();
        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $data = $this->service->getDashboardData($userId);
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/tech/history', 'GET')]
    #[Authorize(['tech'])]
    #[HasPrivilege('view_dashboard_tech')]
    public function getHistory()
    {
        $userId = $this->getUserId();
        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        $data = $this->service->getHistoryData($userId);
        $this->json(['status' => 'success', 'data' => $data]);
    }
}
