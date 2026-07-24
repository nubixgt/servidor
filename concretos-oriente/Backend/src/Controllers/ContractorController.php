<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\ContractorService;
use Exception;

class ContractorController extends Controller
{
    private ContractorService $contractorService;

    public function __construct()
    {
        $this->contractorService = new ContractorService();
    }

    // ----------------------------------------------------------------
    // GET /contractors
    // ----------------------------------------------------------------
    #[Route('/contractors', 'GET')]
    public function index()
    {
        try {
            $data = $this->contractorService->getAllContractors();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /contractors
    // ----------------------------------------------------------------
    #[Route('/contractors', 'POST')]
    public function store()
    {
        try {
            $data = [
                'nombre'             => trim($_POST['nombre'] ?? ''),
                'telefono'           => trim($_POST['telefono'] ?? '') ?: null,
                'correo_electronico' => trim($_POST['correo_electronico'] ?? '') ?: null,
            ];

            $this->contractorService->createContractor($data);

            $this->json(['status' => 'success', 'message' => 'Contratista registrado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /contractors/{id}
    // ----------------------------------------------------------------
    #[Route('/contractors/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'nombre'             => trim($_POST['nombre'] ?? ''),
                'telefono'           => trim($_POST['telefono'] ?? '') ?: null,
                'correo_electronico' => trim($_POST['correo_electronico'] ?? '') ?: null,
            ];

            $this->contractorService->updateContractor((int)$id, $data);

            $this->json(['status' => 'success', 'message' => 'Contratista actualizado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /contractors/{id}
    // ----------------------------------------------------------------
    #[Route('/contractors/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->contractorService->deleteContractor((int)$id);
            $this->json(['status' => 'success', 'message' => 'Contratista eliminado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /contractors/{id}/summary
    // ----------------------------------------------------------------
    #[Route('/contractors/{id}/summary', 'GET')]
    public function summary($id)
    {
        try {
            $data = $this->contractorService->getSummary((int)$id);
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /contractors/{id}/projects
    // ----------------------------------------------------------------
    #[Route('/contractors/{id}/projects', 'POST')]
    public function assignProject($id)
    {
        try {
            $data = [
                'project_id'       => $_POST['project_id'] ?? null,
                'monto_contratado' => $_POST['monto_contratado'] ?? 0,
                'fecha_asignacion' => trim($_POST['fecha_asignacion'] ?? ''),
                'observaciones'    => trim($_POST['observaciones'] ?? '') ?: null,
            ];

            $this->contractorService->assignProject((int)$id, $data);

            $this->json(['status' => 'success', 'message' => 'Proyecto asignado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /contractors/{id}/projects/{projectId}
    // ----------------------------------------------------------------
    #[Route('/contractors/{id}/projects/{projectId}', 'DELETE')]
    public function removeProject($id, $projectId)
    {
        try {
            $this->contractorService->removeProjectAssignment((int)$id, (int)$projectId);
            $this->json(['status' => 'success', 'message' => 'Asignación eliminada correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
