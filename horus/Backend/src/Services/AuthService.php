<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function login(string $username, string $password)
    {
        $user = $this->repository->findByUsername($username);

        if (!$user) {
            throw new \Exception("Credenciales incorrectas");
        }

        if ($user->activo === 0) {
            throw new \Exception("Esta cuenta ha sido desactivada. Contacta al administrador.");
        }

        if (!password_verify($password, $user->password)) {
            throw new \Exception("Credenciales incorrectas");
        }

        $payload = [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'exp' => time() + (60 * 60 * 24) // 24 hours expiration
        ];

        return JwtUtils::generate($payload);
    }
}
