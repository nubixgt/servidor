<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Exceptions\ApiException;
use App\Services\AuthService;
use App\Utils\Auth;

//   POST /auth/login { username, password } -> { token, user }
//   GET  /auth/me                           -> { user }
class AuthController extends Controller
{
    #[Route('/auth/login', 'POST')]
    public function login()
    {
        header('Cache-Control: no-store');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json(['error' => 'Solicitud inválida.'], 400);
        }

        try {
            $this->json((new AuthService())->login($data['username'] ?? null, $data['password'] ?? null));
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/auth/me', 'GET')]
    #[Authorize(['admin', 'vendedor'])]
    public function me()
    {
        header('Cache-Control: no-store');
        $this->json(['user' => Auth::user()]);
    }
}
