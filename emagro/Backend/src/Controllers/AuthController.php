<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\AuthService;
use App\Utils\Response;

class AuthController extends Controller
{
    #[Route('/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['usuario']) || !isset($data['contrasena'])) {
            Response::error('Usuario y contrasena son requeridos', 400);
        }

        $service = new AuthService();

        try {
            $result = $service->login($data['usuario'], $data['contrasena']);
            Response::success($result, 'Login successful');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 401);
        }
    }
}
