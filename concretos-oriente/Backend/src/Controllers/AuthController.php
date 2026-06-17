<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\UserRepository;
use App\Utils\JwtUtils;
use Exception;

class AuthController extends Controller
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    #[Route('/login', 'POST')]
    public function login()
    {
        try {
            // Leer el cuerpo de la petición (JSON)
            $input = json_decode(file_get_contents('php://input'), true);

            $usuario = $input['usuario'] ?? '';
            $password = $input['password'] ?? '';

            if (empty($usuario) || empty($password)) {
                $this->json([
                    "status" => "error",
                    "message" => "Usuario y contraseña son requeridos"
                ], 400);
                return;
            }

            $user = $this->userRepository->findByUsuarioForAuth($usuario);

            if (!$user) {
                $this->json([
                    "status" => "error",
                    "message" => "Credenciales incorrectas o usuario inactivo"
                ], 401);
                return;
            }

            if (!password_verify($password, $user['password'])) {
                $this->json([
                    "status" => "error",
                    "message" => "Credenciales incorrectas o usuario inactivo"
                ], 401);
                return;
            }

            $permisosArray = [];
            if (!empty($user['permisos'])) {
                $permisosArray = json_decode($user['permisos'], true);
            }

            // Generar token JWT
            $payload = [
                'id' => $user['id'],
                'usuario' => $user['usuario'],
                'role' => $user['rol'],
                'permisos' => $permisosArray,
                'exp' => time() + (60 * 60 * 24 * 7) // 7 días de validez
            ];

            $token = JwtUtils::generate($payload);

            // Eliminar password del resultado para enviar al frontend
            unset($user['password']);

            $this->json([
                "status" => "success",
                "message" => "Inicio de sesión exitoso",
                "token" => $token,
                "user" => $user
            ]);

        } catch (Exception $e) {
            $this->json([
                "status" => "error",
                "message" => "Ocurrió un error en el servidor: " . $e->getMessage()
            ], 500);
        }
    }
}
