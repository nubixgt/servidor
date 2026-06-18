<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\BudgetExtensionService;
use Exception;

class BudgetExtensionController extends Controller
{
    private BudgetExtensionService $service;

    public function __construct()
    {
        $this->service = new BudgetExtensionService();
    }

    #[Route('/projects/{id}/budget-extensions', 'GET')]
    public function getByProject(string $id): void
    {
        try {
            $data = $this->service->getByProject((int)$id);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/projects/{id}/budget-extensions', 'POST')]
    public function create(string $id): void
    {
        try {
            $user = $this->getUser();
            $data = [
                'project_id'      => (int)$id,
                'monto'           => $_POST['monto']           ?? null,
                'tipo_ampliacion' => $_POST['tipo_ampliacion'] ?? null,
                'created_by'      => $user ? $user['id'] : null,
            ];
            $filesData = $_FILES['documentos'] ?? null;

            $this->service->create($data, $filesData);

            $this->json(['status' => 'success', 'message' => 'Ampliación registrada correctamente.']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
