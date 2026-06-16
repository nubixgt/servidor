<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\EgresoService;
use App\DTOs\EgresoDTO;
use App\Attributes\Route;

class EgresoController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new EgresoService();
    }

    #[Route('/egresos', 'GET')]
    public function index()
    {
        try {
            $egresos = $this->service->getAll();
            $this->json(['success' => true, 'data' => $egresos]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/egresos/([0-9]+)', 'GET')]
    public function show($id)
    {
        try {
            $egreso = $this->service->getById($id);
            if (!$egreso) {
                $this->json(['success' => false, 'error' => 'Egreso no encontrado'], 404);
                return;
            }
            $this->json(['success' => true, 'data' => $egreso]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/egresos', 'POST')]
    public function store()
    {
        try {
            $dto = EgresoDTO::fromRequest($_POST);
            $egreso = $this->service->create($dto);
            $this->json(['success' => true, 'data' => $egreso], 201);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    #[Route('/egresos/([0-9]+)', 'POST')]
    public function update($id)
    {
        try {
            $dto = EgresoDTO::fromRequest($_POST);
            $egreso = $this->service->update($id, $dto);
            $this->json(['success' => true, 'data' => $egreso]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    #[Route('/egresos/([0-9]+)', 'DELETE')]
    public function destroy($id)
    {
        try {
            $success = $this->service->delete($id);
            if ($success) {
                $this->json(['success' => true, 'message' => 'Egreso eliminado']);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
