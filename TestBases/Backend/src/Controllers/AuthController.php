<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\Route;
use App\Core\Auth;
use App\Core\Controller;
use App\DTOs\LoginDTO;
use App\Services\AuthService;
use App\Utils\Response;

class AuthController extends Controller
{
    #[Route('/auth/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $resultado = (new AuthService())->login(LoginDTO::fromRequest(is_array($data) ? $data : []));
        Response::success($resultado, 'Sesión iniciada');
    }

    #[Route('/auth/me', 'GET')]
    #[Authorize(['admin', 'tecnico'])]
    public function me()
    {
        Response::success((new AuthService())->usuarioActual(Auth::userId()));
    }
}
