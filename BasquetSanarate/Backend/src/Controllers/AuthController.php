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
        $dto = LoginDTO::fromRequest($this->body());
        $this->run(fn () => (new AuthService())->attempt($dto));
    }

    #[Route('/auth/me', 'GET')]
    #[Authorize(['admin'])]
    public function me()
    {
        $this->run(fn () => (new AuthService())->currentUser());
    }
}
