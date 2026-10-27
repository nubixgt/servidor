<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\Route;
use App\Core\Controller;
use App\Core\Request;
use App\DTOs\CambioClaveDTO;
use App\DTOs\LoginDTO;
use App\Services\AuthService;

class AuthController extends Controller
{
    private const COOKIE = 'pam_rt';

    #[Route('/auth/login', 'POST')]
    public function login()
    {
        [$data, $refresh] = (new AuthService())->login(LoginDTO::fromRequest($this->body()));
        $this->cookie($refresh);
        $this->json($data);
    }

    #[Route('/auth/refresh', 'POST')]
    public function refresh()
    {
        try {
            [$data, $refresh] = (new AuthService())->refresh($_COOKIE[self::COOKIE] ?? null);
        } catch (\App\Core\HttpException $e) {
            $this->borrarCookie();
            throw $e;
        }
        $this->cookie($refresh);
        $this->json($data);
    }

    #[Route('/auth/logout', 'POST')]
    public function logout()
    {
        (new AuthService())->logout($_COOKIE[self::COOKIE] ?? null);
        $this->borrarCookie();
        $this->json(['ok' => true]);
    }

    #[Route('/auth/yo', 'GET')]
    #[Authorize]
    public function yo()
    {
        $this->json($this->user()->toArray());
    }

    #[Route('/auth/cambiar-clave', 'POST')]
    #[Authorize]
    public function cambiarClave()
    {
        (new AuthService())->cambiarClave($this->user(), CambioClaveDTO::fromRequest($this->body()));
        $this->json(['ok' => true]);
    }

    /** La cookie solo viaja a /auth: el refresh token no se envía en el resto de peticiones. */
    private function cookiePath(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/auth';
    }

    private function cookie(string $token): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        setcookie(self::COOKIE, $token, [
            'expires' => time() + $config['refresh_ttl_h'] * 3600,
            'path' => $this->cookiePath(),
            'secure' => $config['cookie_secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private function borrarCookie(): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600,
            'path' => $this->cookiePath(),
            'secure' => $config['cookie_secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}
