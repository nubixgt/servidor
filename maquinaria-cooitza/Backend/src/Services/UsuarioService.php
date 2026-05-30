<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Utils\JwtUtils;

class UsuarioService
{
    private $usuarioRepository;

    public function __construct()
    {
        $this->usuarioRepository = new UsuarioRepository();
    }

    public function login($username, $password)
    {
        $usuario = $this->usuarioRepository->findByUsername($username);

        if (!$usuario || $usuario->status !== 'activo') {
            return ['success' => false, 'message' => 'Usuario no encontrado o inactivo'];
        }

        if (!password_verify($password, $usuario->password_hash)) {
            return ['success' => false, 'message' => 'Contraseña incorrecta'];
        }

        $payload = [
            'id' => $usuario->id,
            'username' => $usuario->username,
            'role' => $usuario->role,
            'full_name' => $usuario->full_name,
            'exp' => time() + (86400 * 7) // 7 days expiration
        ];

        $token = JwtUtils::generate($payload);

        return [
            'success' => true, 
            'token' => $token,
            'user' => [
                'username' => $usuario->username,
                'role' => $usuario->role,
                'full_name' => $usuario->full_name
            ]
        ];
    }
}
