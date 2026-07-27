<?php
namespace Controllers;

use Core\Response;
use Models\EquipoModel;
use Utils\JwtUtils;

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $usuario = $input['usuario'] ?? $_POST['usuario'] ?? '';
        $password = $input['password'] ?? $_POST['password'] ?? '';

        if (empty($usuario) || empty($password)) {
            Response::error('Usuario y contraseña son requeridos', 400);
        }

        $equipoModel = new EquipoModel();
        $equipo = $equipoModel->findByUsuario($usuario);

        if (!$equipo) {
            Response::error('Credenciales inválidas', 401);
        }

        if (password_verify($password, $equipo['password_hash'])) {
            // Generate token
            $payload = [
                'equipo_id' => $equipo['id'],
                'usuario' => $equipo['usuario'],
                'exp' => time() + (86400 * 7) // 7 days expiration
            ];
            $token = JwtUtils::generateToken($payload);

            Response::json([
                'message' => 'Login exitoso',
                'token' => $token,
                'equipo' => [
                    'id' => $equipo['id'],
                    'nombre' => $equipo['nombre'],
                    'representante' => $equipo['representante']
                ]
            ]);
        } else {
            Response::error('Credenciales inválidas', 401);
        }
    }
}
