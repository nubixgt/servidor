<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\IncidentRepository;
use Exception;

class IncidentController extends Controller
{
    private IncidentRepository $repository;

    public function __construct()
    {
        $this->repository = new IncidentRepository();
    }

    #[Route('/incidents', 'GET')]
    public function index()
    {
        try {
            $incidents = $this->repository->findAllWithPersonnel();
            $this->json(['status' => 'success', 'data' => $incidents]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/incidents', 'POST')]
    public function store()
    {
        try {
            $data = [
                'personnel_id' => (isset($_POST['personnel_id']) && $_POST['personnel_id'] !== '')
                                    ? (int)$_POST['personnel_id'] : null,
                'texto'        => trim($_POST['texto']  ?? ''),
                'fecha'        => trim($_POST['fecha']  ?? ''),
                'motivo'       => trim($_POST['motivo'] ?? ''),
            ];

            if (!$data['personnel_id'] || empty($data['texto']) || empty($data['fecha']) || empty($data['motivo'])) {
                throw new Exception('Todos los campos son obligatorios.', 400);
            }

            $id = $this->repository->create($data);

            $this->json(['status' => 'success', 'message' => 'Incidencia registrada correctamente', 'id' => $id], 201);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/incidents/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $incident = $this->repository->findById((int)$id);
            if (!$incident) {
                throw new Exception('Incidencia no encontrada.', 404);
            }

            $this->repository->delete((int)$id);

            $this->json(['status' => 'success', 'message' => 'Incidencia eliminada correctamente']);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
