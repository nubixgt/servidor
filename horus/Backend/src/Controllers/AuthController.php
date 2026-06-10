<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\AuthService;

class AuthController extends Controller
{
    #[Route('/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->json(['status' => 'error', 'error' => 'Usuario y contraseña son requeridos'], 400);
            return;
        }

        $service = new AuthService();

        try {
            $token = $service->login($username, $password);
            $this->json([
                'status' => 'success',
                'message' => 'Inicio de sesión exitoso',
                'token' => $token
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
