<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Services\AuthService;

class AuthController extends Controller
{
    #[Route('/auth/register', 'POST')]
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = RegisterDTO::fromRequest($data);
        $service = new AuthService();

        try {
            $result = $service->register($dto);
            $this->json([
                'status' => 'success',
                'message' => 'Cuenta creada correctamente',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/auth/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = LoginDTO::fromRequest($data);
        $service = new AuthService();

        try {
            $result = $service->login($dto);
            $this->json([
                'status' => 'success',
                'message' => 'Inicio de sesión correcto',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 401);
        }
    }
}
