<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\DTOs\LoginDTO;
use App\Services\AuthService;
use App\Utils\Response;

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
            Response::success($result, 'Inicio de sesión exitoso');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 401);
        }
    }
}
