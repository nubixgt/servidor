<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function login($usuario, $contrasena)
    {
        $user = $this->userRepo->findByUsername($usuario);

        if (!$user) {
            throw new \Exception("Invalid credentials");
        }

        if (!password_verify($contrasena, $user->contrasena)) {
            throw new \Exception("Credenciales inválidas");
        }

        if ($user->estado !== 'activo') {
            throw new \Exception("La cuenta está inactiva o de baja");
        }

        $authData = $this->userRepo->getRoleWithPrivileges($user->id);

        $payload = [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'usuario' => $user->usuario,
            'rol' => $authData['role'],
            'privileges' => $authData['privileges']
        ];

        return [
            'token' => JwtUtils::generate($payload),
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'rol' => $authData['role']
            ]
        ];
    }
}
