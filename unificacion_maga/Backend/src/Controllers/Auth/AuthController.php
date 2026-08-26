<?php
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Auth\AuthService;

class AuthController extends Controller
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    #[Route('/auth/login', 'POST')]
    public function login()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($username) || empty($password)) {
                $this->json(['success' => false, 'message' => 'Faltan credenciales'], 400);
                return;
            }

            $result = $this->authService->login($username, $password);
            
            $this->json([
                'success' => true,
                'token' => $result['token'],
                'user' => $result['user']
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 401);
        } catch (\Error $e) {
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    #[Route('/auth/validate', 'GET')]
    public function validate()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $userData = $this->authService->validateToken($token);
            
            if ($userData) {
                $this->json(['success' => true, 'user' => $userData]);
                return;
            }
        }

        $this->json(['success' => false, 'message' => 'Token inválido o expirado'], 401);
    }
}
