<?php
namespace App\Controllers\Audit;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Audit\AuditService;

class AuditController extends Controller
{
    private $auditService;

    public function __construct()
    {
        $this->auditService = new AuditService();
    }

    #[Route('/audit', 'GET')]
    public function getLogs()
    {
        // Require ADMIN role
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        $token = $matches[1];
        $payload = \App\Utils\JwtUtils::validate($token);
        $userRole = strtoupper($payload['role'] ?? $payload['rol'] ?? '');
        
        if (!$payload || $userRole !== 'ADMIN') {
            $this->json(['success' => false, 'message' => 'Permisos insuficientes. Solo administradores.'], 403);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $logs = $this->auditService->getLogs($limit, $offset);

        // Decode JSON fields for frontend convenience
        foreach ($logs as &$log) {
            $log['old_values'] = isset($log['old_values']) ? json_decode($log['old_values'], true) : null;
            $log['new_values'] = isset($log['new_values']) ? json_decode($log['new_values'], true) : null;
        }

        $this->json(['success' => true, 'data' => $logs]);
    }
}
