<?php
namespace App\Controllers;

use App\Core\Response;
use App\Core\Route;
use App\Services\VehiculoService;

class VehiculoController
{
    private $service;

    public function __construct()
    {
        $this->service = new VehiculoService();
    }

    #[Route('/vehiculos', 'GET')]
    public function getAll()
    {
        $vehiculos = $this->service->getAll();
        Response::json([
            'status' => 'success',
            'data' => $vehiculos
        ]);
    }

    #[Route('/vehiculos', 'POST')]
    public function create()
    {
        // Usando form-data porque incluye archivo
        $data = $_POST;
        $file = $_FILES['foto'] ?? null;

        if (empty($data['marca']) || empty($data['placa'])) {
            Response::json(['message' => 'Marca y placa son obligatorios'], 400);
            return;
        }

        $result = $this->service->create($data, $file);
        
        if ($result['success']) {
            Response::json(['status' => 'success', 'id' => $result['id']], 201);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/vehiculos/update', 'POST')]
    public function update()
    {
        $data = $_POST;
        $file = $_FILES['foto'] ?? null;
        $id = $data['id'] ?? null;

        if (!$id) {
            Response::json(['message' => 'ID no proporcionado'], 400);
            return;
        }

        $result = $this->service->update($id, $data, $file);
        
        if ($result['success']) {
            Response::json(['status' => 'success'], 200);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/vehiculos/delete', 'POST')]
    public function delete()
    {
        // Puede venir por JSON si es un fetch puro sin FormData
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        $id = $data['id'] ?? null;

        if (!$id) {
            Response::json(['message' => 'ID no proporcionado'], 400);
            return;
        }

        $result = $this->service->delete($id);
        
        if ($result['success']) {
            Response::json(['status' => 'success'], 200);
        } else {
            Response::json(['message' => 'Error al eliminar vehículo'], 500);
        }
    }
}
