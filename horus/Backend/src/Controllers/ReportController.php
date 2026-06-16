<?php
namespace App\Controllers;

use App\Services\ReportService;
use App\Repositories\ReportRepository;
use App\Core\Database;
use Exception;

class ReportController {
    private $service;

    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $repository = new ReportRepository($db);
        $this->service = new ReportService($repository);
    }

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
}
