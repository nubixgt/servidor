<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\UsuarioModel;
use App\Utils\JwtUtils;

class AuthController extends Controller
{
    #[Route('/auth/login', 'POST')]
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $username = $data['usuario'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->json(['error' => 'Usuario y contraseña son requeridos'], 400);
            return;
        }

        $model = new UsuarioModel();
        $user = $model->findByUsername($username);

        if (!$user) {
            $this->json(['error' => 'Credenciales inválidas'], 401);
            return;
        }

        if ($user['estado'] != 1) {
            $this->json(['error' => 'Usuario inactivo'], 403);
            return;
        }

        // Validación de contraseña
        if (password_verify($password, $user['password_hash'])) {
            
            // Actualizar el último acceso
            $model->updateLastAccess($user['id']);

            // Generar Token JWT
            $payload = [
                'id' => $user['id'],
                'nombre' => $user['nombre_completo'],
                'usuario' => $user['usuario'],
                'rol' => $user['rol'],
                'categoria' => $user['categoria_asignada'],
                'exp' => time() + (86400 * 7) // 7 días de validez
            ];

            $token = JwtUtils::generate($payload);

            $this->json([
                'status' => 'success',
                'message' => 'Autenticación exitosa',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre_completo'],
                    'rol' => $user['rol'],
                    'categoria' => $user['categoria_asignada'] // Null para admins
                ]
            ]);
        } else {
            $this->json(['error' => 'Credenciales inválidas'], 401);
        }
    }
}
