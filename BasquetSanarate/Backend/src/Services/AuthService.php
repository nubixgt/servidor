<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\LoginDTO;
use App\Entities\Usuario;
use App\Repositories\UsuarioRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private const TOKEN_TTL = 86400 * 7; // 7 días

    private UsuarioRepository $repository;

    public function __construct()
    {
        $this->repository = new UsuarioRepository();
    }

    /**
     * Verifica credenciales y devuelve ['token' => ..., 'user' => [...]].
     */
    public function attempt(LoginDTO $dto): array
    {
        if ($dto->usuario === '' || $dto->password === '') {
            throw HttpException::validation('Usuario y contraseña son requeridos');
        }

        $row = $this->repository->findByUsuario($dto->usuario);

        if (!$row || !password_verify($dto->password, $row['password_hash'])) {
            throw HttpException::unauthorized('Credenciales inválidas');
        }

        if (!(bool) $row['activo']) {
            throw HttpException::unauthorized('La cuenta está desactivada');
        }

        $usuario = Usuario::fromRow($row);

        $payload = [
            'sub' => $usuario->id,
            'usuario' => $usuario->usuario,
            'nombre' => $usuario->nombre,
            'role' => 'admin', // el Router lee $payload['role']
            'iat' => time(),
            'exp' => time() + self::TOKEN_TTL,
        ];

        return [
            'token' => JwtUtils::generate($payload),
            'user' => $usuario->toArray(),
        ];
    }

    /**
     * Usuario actual a partir del token ya validado por el Router.
     */
    public function currentUser(): array
    {
        $token = JwtUtils::bearerToken();
        $payload = $token ? JwtUtils::validate($token) : false;

        if (!$payload || empty($payload['sub'])) {
            throw HttpException::unauthorized();
        }

        $row = $this->repository->findById((int) $payload['sub']);

        if (!$row || !(bool) $row['activo']) {
            throw HttpException::unauthorized();
        }

        return Usuario::fromRow($row)->toArray();
    }
}
