<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\UserRepository;
use App\Utils\JwtUtils;
use PDOException;

class AuthService
{
    // Hash válido de relleno: se verifica igual aunque el usuario no exista (tiempo constante).
    private const DUMMY_HASH = '$2y$10$ZmDSLCThjgP96eM4BiRxPORj80N6vNyird/cXRZLGf1DbiY3hkkuy';

    public function login(mixed $username, mixed $password): array
    {
        if (!is_string($username) || !is_string($password) || $username === '' || $password === '') {
            throw new ApiException('Ingresa tu usuario y contraseña.', 400);
        }

        try {
            $user = (new UserRepository())->findByUsername(strtolower(trim($username)));
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new ApiException('No se pudo iniciar sesión. Intenta nuevamente.', 503);
        }

        $ok = password_verify($password, $user->passwordHash ?? self::DUMMY_HASH);

        if (!$user || !$ok || !$user->active) {
            usleep(400000); // frena intentos de fuerza bruta
            throw new ApiException('Usuario o contraseña incorrectos.', 401);
        }

        $ttl = JwtUtils::config()['ttl'];
        $token = JwtUtils::generate([
            'sub' => $user->id,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + $ttl,
        ]);

        return ['token' => $token, 'user' => $user->toArray()];
    }
}
