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
        
        $usuario = $data['usuario'] ?? null;
        $password = $data['password'] ?? null;
        
        if (!$usuario || !$password) {
            $this->json(['error' => 'Usuario y contraseña son requeridos'], 400);
            return;
        }
        
        $service = new AuthService();
        
        try {
            $result = $service->login($usuario, $password);
            if ($result) {
                $this->json([
                    'message' => 'Autenticación exitosa',
                    'token' => $result['token'],
                    'user' => $result['user']
                ]);
            } else {
                $this->json(['error' => 'Credenciales incorrectas'], 401);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
