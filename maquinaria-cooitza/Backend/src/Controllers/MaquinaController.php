<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\MaquinaService;
use App\Utils\Response;

class MaquinaController extends Controller
{
    private $maquinaService;

    public function __construct()
    {
        $this->maquinaService = new MaquinaService();
    }

    #[Route('/maquinas', 'GET')]
    public function index()
    {
        $maquinas = $this->maquinaService->getAll();
        Response::json(['status' => 'success', 'data' => $maquinas], 200);
    }

    #[Route('/maquinas', 'POST')]
    public function create()
    {
        // Validar multipart/form-data
        $data = $_POST;
        $file = $_FILES['foto'] ?? null;

        if (empty($data['marca']) || empty($data['tipo']) || empty($data['identificador'])) {
            Response::json(['message' => 'Faltan campos obligatorios'], 400);
            return;
        }

        $result = $this->maquinaService->create($data, $file);

        if ($result['success']) {
            Response::json(['message' => 'Maquinaria registrada con éxito', 'id' => $result['id']], 201);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/maquinas/update', 'POST')]
    public function update()
    {
        $data = $_POST;
        $id = $data['id'] ?? null;
        $file = $_FILES['foto'] ?? null;

        if (!$id) {
            Response::json(['message' => 'Falta el ID'], 400);
            return;
        }

        $result = $this->maquinaService->update($id, $data, $file);

        if ($result['success']) {
            Response::json(['message' => 'Maquinaria actualizada con éxito'], 200);
        } else {
            Response::json(['message' => $result['message'] ?? 'Error al actualizar'], 500);
        }
    }

    #[Route('/maquinas/delete', 'POST')]
    public function delete()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            Response::json(['message' => 'Falta el ID'], 400);
            return;
        }

        $result = $this->maquinaService->delete($id);

        if ($result['success']) {
            Response::json(['message' => 'Eliminado correctamente'], 200);
        } else {
            Response::json(['message' => 'Error al eliminar'], 500);
        }
    }
}
