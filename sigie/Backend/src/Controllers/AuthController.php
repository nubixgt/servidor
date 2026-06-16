<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\UsuarioModel;
use App\Models\InspectorModel;
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

        $userModel = new UsuarioModel();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            $this->json(['error' => 'Credenciales inválidas'], 401);
            return;
        }

        if ($user['estado'] != 1) {
            $this->json(['error' => 'Usuario inactivo'], 403);
            return;
        }

        // Validate password
        if (password_verify($password, $user['password_hash'])) {
            $userModel->updateLastAccess($user['id']);

            // If the role is inspector, look up the inspector_id
            $inspectorId = null;
            if ($user['rol'] === 'inspector') {
                $inspectorModel = new InspectorModel();
                $inspector = $inspectorModel->findByUserId($user['id']);
                if ($inspector) {
                    $inspectorId = $inspector['id'];
                }
            }

            // Generate JWT Token
            $payload = [
                'id' => $user['id'],
                'nombre' => $user['nombre_completo'],
                'usuario' => $user['usuario'],
                'rol' => $user['rol'],
                'inspector_id' => $inspectorId,
                'exp' => time() + (86400 * 7) // 7 days expiration
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
                    'inspector_id' => $inspectorId
                ]
            ]);
        } else {
            $this->json(['error' => 'Credenciales inválidas'], 401);
        }
    }
}
