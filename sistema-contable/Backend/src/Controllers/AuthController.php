<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\AuthService;

class AuthController extends Controller
{
    #[Route('/auth/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->json(['error' => 'Usuario y contraseña requeridos'], 400);
        }

        $service = new AuthService();
        
        try {
            $result = $service->login($username, $password);
            
            $this->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 401);
        }
    }
}
