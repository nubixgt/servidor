<?php
namespace App\Controllers;

use App\Services\ReportService;
use App\Repositories\ReportRepository;
use App\Utils\Database;
use App\Attributes\Route;
use App\Core\Controller;
use Exception;

class ReportController extends Controller {
    private $service;

    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $repository = new ReportRepository($db);
        $this->service = new ReportService($repository);
    }

    #[Route('/reports/dashboard', 'GET')]
    public function getDashboardData() {
        try {
            $data = $this->service->getDashboardData();
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    #[Route('/reports/cliente/([0-9]+)', 'GET')]
    public function getClienteReportData(int $id) {
        try {
            $data = $this->service->getClienteReport($id);
            if (!$data) {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Cliente no encontrado"
                ]);
                return;
            }
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
