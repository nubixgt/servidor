<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Services\AdminService;
use App\Utils\JwtUtils;

class AdminController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new AdminService();
    }

    private function getUserId()
    {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        $payload = JwtUtils::validate($token);
        return $payload ? $payload['id'] : null;
    }

    #[Route('/admin/dashboard', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_dashboard_admin')]
    public function getDashboard()
    {
        $data = $this->service->getDashboardData();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/locations', 'GET')]
    #[Authorize(['admin', 'tech'])]
    #[HasPrivilege('view_locations')]
    public function getLocations()
    {
        $data = $this->service->getLocations();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/reports', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_reports')]
    public function getReports()
    {
        $data = $this->service->getReports();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/users', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function getUsers()
    {
        $data = $this->service->getUsers();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/transactions', 'POST')]
    #[Authorize(['admin'])]
    #[HasPrivilege('create_financial_transaction')]
    public function createTransaction()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = $this->getUserId();

        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
        }

        try {
            $id = $this->service->createTransaction($data, $userId);
            $this->json(['status' => 'success', 'message' => 'Transacción creada', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
