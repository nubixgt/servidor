<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\DTOs\LoginDTO;
use App\Utils\JwtUtils;

class AuthService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new UsuarioRepository();
    }

    public function login(LoginDTO $dto): array
    {
        if (empty($dto->usuario) || empty($dto->password)) {
            throw new \Exception('Usuario y contraseña son obligatorios');
        }

        $usuario = $this->repository->findByUsuario($dto->usuario);

        if (!$usuario || !password_verify($dto->password, $usuario->password)) {
            throw new \Exception('Usuario o contraseña incorrectos');
        }

        $token = JwtUtils::generate([
            'sub' => $usuario->id,
            'usuario' => $usuario->usuario,
            'role' => $usuario->rol,
            'exp' => time() + (60 * 60 * 8), // Token válido por 8 horas
        ]);

        return [
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'usuario' => $usuario->usuario,
                'nombre' => $usuario->nombre,
                'rol' => $usuario->rol,
            ],
        ];
    }
}
