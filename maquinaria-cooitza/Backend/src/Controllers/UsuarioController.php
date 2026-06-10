<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\UsuarioService;
use App\Utils\Response;
use App\Attributes\Route;

class UsuarioController extends Controller
{
    private $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
    }

    #[Route('/usuarios/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            Response::json(['message' => 'Faltan credenciales'], 400);
            return;
        }

        $result = $this->usuarioService->login($username, $password);

        if ($result['success']) {
            Response::json([
                'message' => 'Login exitoso',
                'token' => $result['token'],
                'user' => $result['user']
            ], 200);
        } else {
            Response::json(['message' => $result['message']], 401);
        }
    }

    #[Route('/usuarios', 'GET')]
    public function getAll()
    {
        $usuarios = $this->usuarioService->getAll();
        Response::json([
            'status' => 'success',
            'data' => $usuarios
        ]);
    }

    #[Route('/usuarios', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        
        if (empty($data['username']) || empty($data['full_name'])) {
            Response::json(['message' => 'Faltan datos obligatorios'], 400);
            return;
        }

        $result = $this->usuarioService->create($data);
        if ($result['success']) {
            Response::json(['status' => 'success', 'id' => $result['id']], 201);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/usuarios/update', 'POST')]
    public function update()
    {
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        
        if (empty($data['id'])) {
            Response::json(['message' => 'ID no proporcionado'], 400);
            return;
        }

        $result = $this->usuarioService->update($data);
        if ($result['success']) {
            Response::json(['status' => 'success'], 200);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/usuarios/status', 'POST')]
    public function updateStatus()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['status'])) {
            Response::json(['message' => 'Faltan datos obligatorios'], 400);
            return;
        }

        $result = $this->usuarioService->updateStatus($data['id'], $data['status']);
        if ($result['success']) {
            Response::json(['status' => 'success'], 200);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }

    #[Route('/usuarios/delete', 'POST')]
    public function delete()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            Response::json(['message' => 'ID no proporcionado'], 400);
            return;
        }

        $result = $this->usuarioService->delete($id);
        if ($result['success']) {
            Response::json(['status' => 'success'], 200);
        } else {
            Response::json(['message' => $result['message']], 500);
        }
    }
}
