<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use App\Utils\JwtUtils;

class AuthController extends Controller
{
    private const TOKEN_TTL = 8 * 3600; // 8 horas

    #[Route('/auth/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $usuario = trim((string)($data['usuario'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($usuario === '' || $password === '') {
            $this->json(['error' => 'Ingresa tu usuario y contraseña.'], 400);
        }

        $stmt = Database::getInstance()->getConnection()
            ->prepare('SELECT id, usuario, password, rol, activo FROM usuarios WHERE usuario = :u LIMIT 1');
        $stmt->execute(['u' => $usuario]);
        $user = $stmt->fetch();

        // Mismo mensaje si no existe, está inactivo o la contraseña falla (no revela cuál).
        if (!$user || !(int)$user['activo'] || !password_verify($password, $user['password'])) {
            $this->json(['error' => 'Usuario o contraseña incorrectos.'], 401);
        }

        $now = time();
        $token = JwtUtils::generate([
            'sub' => (int)$user['id'],
            'usuario' => $user['usuario'],
            'role' => $user['rol'],
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL,
        ]);

        $this->json([
            'token' => $token,
            'user' => ['id' => (int)$user['id'], 'usuario' => $user['usuario'], 'rol' => $user['rol']],
        ]);
    }
}
