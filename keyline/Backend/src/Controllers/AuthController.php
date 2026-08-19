<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\DTOs\LoginDTO;
use App\Services\AuthService;

class AuthController extends Controller
{
    #[Route('/auth/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = LoginDTO::fromRequest($data);
        $service = new AuthService();

        try {
            $result = $service->login($dto);
            $this->json($result);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 401);
        }
    }

    #[Route('/auth/me', 'GET')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function me()
    {
        $service = new AuthService();
        try {
            $user = $service->me();
            $this->json(['user' => $user]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 401);
        }
    }

    #[Route('/auth/cambiar-password', 'POST')]
    #[Authorize(['tecnico', 'supervisor', 'administrador'])]
    public function cambiarPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $service = new AuthService();
        try {
            $service->cambiarPassword((string)($data['actual'] ?? ''), (string)($data['nueva'] ?? ''));
            $this->json(['ok' => true]);
        } catch (\Exception $e) {
            $code = $e->getCode();
            $this->json(['error' => $e->getMessage()], in_array($code, [400, 401], true) ? $code : 400);
        }
    }
}
