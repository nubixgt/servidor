<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\RegistroVacunacionDTO;
use App\Services\RegistroVacunacionService;

class RegistroVacunacionController extends Controller
{
    #[Route('/registros-vacunacion', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $dto = RegistroVacunacionDTO::fromRequest($data);
            $service = new RegistroVacunacionService();
            
            if ($service->createRegistro($dto)) {
                $this->json(['status' => 'success', 'message' => 'Registro creado exitosamente.'], 201);
            } else {
                $this->json(['status' => 'error', 'message' => 'No se pudo crear el registro.'], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error interno del servidor.', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/registros-vacunacion', 'GET')]
    public function index()
    {
        try {
            $service = new RegistroVacunacionService();
            $data = $service->getAll();
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error interno del servidor.', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/registros-vacunacion/{id}/estado', 'PUT')]
    public function updateStatus($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $estado = $data['estado'] ?? null;

        if (!$estado) {
            $this->json(['status' => 'error', 'message' => 'El estado es requerido.'], 400);
        }

        try {
            $service = new RegistroVacunacionService();
            if ($service->updateStatus((int)$id, $estado)) {
                $this->json(['status' => 'success', 'message' => 'Estado actualizado.']);
            } else {
                $this->json(['status' => 'error', 'message' => 'No se pudo actualizar el estado.'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error interno.', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/registros-vacunacion/{id}', 'PUT')]
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $dto = RegistroVacunacionDTO::fromRequest($data);
            $service = new RegistroVacunacionService();
            
            if ($service->updateRegistro((int)$id, $dto)) {
                $this->json(['status' => 'success', 'message' => 'Registro actualizado exitosamente.']);
            } else {
                $this->json(['status' => 'error', 'message' => 'No se pudo actualizar el registro.'], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error interno.', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/registros-vacunacion/{id}', 'DELETE')]
    public function delete($id)
    {
        try {
            $service = new RegistroVacunacionService();
            if ($service->deleteRegistro((int)$id)) {
                $this->json(['status' => 'success', 'message' => 'Registro eliminado.']);
            } else {
                $this->json(['status' => 'error', 'message' => 'No se pudo eliminar.'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error interno.', 'details' => $e->getMessage()], 500);
        }
    }
}
