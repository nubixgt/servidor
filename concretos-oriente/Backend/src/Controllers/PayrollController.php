<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\PayrollService;
use Exception;

class PayrollController extends Controller
{
    private PayrollService $payrollService;

    public function __construct()
    {
        $this->payrollService = new PayrollService();
    }

    #[Route('/payrolls/active-personnel', 'GET')]
    public function getActivePersonnel()
    {
        try {
            $personnel = $this->payrollService->getActivePersonnel();
            $this->json(['status' => 'success', 'message' => 'Personal activo', 'data' => $personnel]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al obtener personal: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/payrolls', 'GET')]
    public function getPayrolls()
    {
        try {
            $payrolls = $this->payrollService->getAllPayrolls();
            $this->json(['status' => 'success', 'message' => 'Planillas', 'data' => $payrolls]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error al obtener planillas: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/payrolls', 'POST')]
    public function createPayroll()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $this->payrollService->createPayroll($data);

            $this->json(['status' => 'success', 'message' => 'Planilla generada']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], $code);
        }
    }

    #[Route('/payrolls/details', 'GET')]
    public function getPayrollDetails()
    {
        try {
            $payroll_id = isset($_GET['payroll_id']) ? (int)$_GET['payroll_id'] : null;
            $details = $this->payrollService->getPayrollDetails($payroll_id);

            $this->json(['status' => 'success', 'message' => 'Detalles', 'data' => $details]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], $code);
        }
    }

    #[Route('/payroll-details', 'POST')]
    public function updatePayrollDetail()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $this->payrollService->updatePayrollDetail($data);

            $this->json(['status' => 'success', 'message' => 'Detalle actualizado']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], $code);
        }
    }

    #[Route('/payrolls/pay', 'POST')]
    public function payPayroll()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) $data = $_POST;

            $payroll_id = isset($data['payroll_id']) ? (int)$data['payroll_id'] : null;

            $this->payrollService->payPayroll($payroll_id);

            $this->json(['status' => 'success', 'message' => 'Pagos emitidos']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], $code);
        }
    }
}
