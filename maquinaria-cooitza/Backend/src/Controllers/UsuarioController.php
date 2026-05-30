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
}
