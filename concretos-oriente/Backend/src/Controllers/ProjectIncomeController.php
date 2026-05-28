<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\ProjectIncomeService;
use Exception;

class ProjectIncomeController extends Controller
{
    private ProjectIncomeService $incomeService;

    public function __construct()
    {
        $this->incomeService = new ProjectIncomeService();
    }

    // GET /projects/{projectId}/incomes
    #[Route('/projects/{projectId}/incomes', 'GET')]
    public function getIncomes($projectId)
    {
        try {
            $incomes = $this->incomeService->getProjectIncomes((int)$projectId);
            $summary = $this->incomeService->getProjectIncomeSummary((int)$projectId);
            $totals = $this->incomeService->getProjectTotals((int)$projectId);

            $this->json([
                "status" => "success",
                "data" => [
                    "timeline" => $incomes,
                    "summary" => $summary,
                    "totals" => $totals
                ]
            ]);
        } catch (Exception $e) {
            $this->json([
                "status" => "error",
                "message" => "Error al obtener ingresos: " . $e->getMessage()
            ], 500);
        }
    }

    // POST /projects/{projectId}/incomes
    #[Route('/projects/{projectId}/incomes', 'POST')]
    public function store($projectId)
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                // Si viene por form-data
                $input = $_POST;
                if (isset($input['sources']) && is_string($input['sources'])) {
                    $input['sources'] = json_decode($input['sources'], true);
                }
            }

            $data = [
                'project_id' => (int)$projectId,
                'tipo_cobro' => $input['tipo_cobro'] ?? '',
                'monto_total' => $input['monto_total'] ?? 0,
                'fecha_registro' => $input['fecha_registro'] ?? date('Y-m-d'),
                'periodo_avance' => $input['periodo_avance'] ?? null,
                'observaciones' => $input['observaciones'] ?? null
            ];

            $sourcesData = $input['sources'] ?? [];

            $incomeId = $this->incomeService->registerIncome($data, $sourcesData, $_FILES);

            $this->json([
                "status" => "success",
                "message" => "Ingreso registrado exitosamente.",
                "data" => ["income_id" => $incomeId]
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], $code);
        }
    }

    // POST /project-incomes/sources/{sourceId}
    // (Usamos POST para soportar FormData con archivos)
    #[Route('/project-incomes/sources/{sourceId}', 'POST')]
    public function updateSource($sourceId)
    {
        try {
            $data = [
                'fecha_cobro' => !empty($_POST['fecha_cobro']) ? $_POST['fecha_cobro'] : null,
                'numero_documento' => !empty($_POST['numero_documento']) ? $_POST['numero_documento'] : null,
                'bank_account_id' => !empty($_POST['bank_account_id']) ? $_POST['bank_account_id'] : null,
                'estado' => !empty($_POST['estado']) ? $_POST['estado'] : 'Pendiente'
            ];

            $file = $_FILES['comprobante'] ?? null;

            $this->incomeService->updateSource((int)$sourceId, $data, $file);

            $this->json([
                "status" => "success",
                "message" => "Fuente actualizada exitosamente."
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], $code);
        }
    }
}
