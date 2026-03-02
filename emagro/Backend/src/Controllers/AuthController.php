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

        if (!isset($data['username']) || !isset($data['password'])) {
            Response::error('Username and password are required', 400);
        }

        $service = new AuthService();

        try {
            $token = $service->login($data['username'], $data['password']);
            Response::success(['token' => $token], 'Login successful');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 401);
        }
    }
}
