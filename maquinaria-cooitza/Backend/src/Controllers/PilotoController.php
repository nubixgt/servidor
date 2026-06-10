<?php
namespace App\Controllers;

use App\Utils\Response;
use App\Attributes\Route;
use App\Services\PilotoService;

class PilotoController
{
    private $service;

    public function __construct()
    {
        $this->service = new PilotoService();
    }

    #[Route('/pilotos', 'GET')]
    public function getAll()
    {
        $pilotos = $this->service->getAll();
        Response::json([
            'status' => 'success',
            'data' => $pilotos
        ]);
    }

    #[Route('/pilotos', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        if (empty($data['nombre']) || empty($data['telefono'])) {
            Response::json(['message' => 'Nombre y teléfono son obligatorios'], 400);
            return;
        }

        $result = $this->service->create($data);
        
        if ($result['success']) {
            Response::json(['status' => 'success', 'id' => $result['id']], 201);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/pilotos/update', 'POST')]
    public function update()
    {
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        $id = $data['id'] ?? null;

        if (!$id) {
            Response::json(['message' => 'ID no proporcionado'], 400);
            return;
        }

        $result = $this->service->update($id, $data);
        
        if ($result['success']) {
            Response::json(['status' => 'success'], 200);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/pilotos/delete', 'POST')]
    public function delete()
    {
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
            Response::json(['message' => 'Error al eliminar piloto'], 500);
        }
    }
}
