<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\LoginDTO;
use App\Repositories\UsuarioRepository;
use App\Utils\JwtUtils;

class AuthService
{
    private const MENSAJE_INVALIDO = 'Usuario o contraseña incorrectos';

    private UsuarioRepository $usuarios;
    private array $config;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function login(LoginDTO $dto): array
    {
        if ($dto->usuario === '' || $dto->clave === '') {
            throw new HttpException('Escribe tu usuario y contraseña', 422);
        }
        if (strlen($dto->clave) > 200) {
            throw new HttpException(self::MENSAJE_INVALIDO, 401);
        }

        $usuario = $this->usuarios->findByUsuario($dto->usuario);

        if ($usuario === null) {
            // Se gasta el mismo tiempo que con un usuario real para no revelar cuáles existen
            password_verify($dto->clave, password_hash('relleno', PASSWORD_BCRYPT));
            throw new HttpException(self::MENSAJE_INVALIDO, 401);
        }

        $espera = $this->usuarios->segundosDeBloqueo($usuario->id);
        if ($espera > 0) {
            $min = (int) ceil($espera / 60);
            throw new HttpException("Demasiados intentos fallidos. Intenta de nuevo en $min minuto" . ($min === 1 ? '' : 's'), 429);
        }

        if (!password_verify($dto->clave, $usuario->claveHash) || !$usuario->activo) {
            $this->usuarios->registrarFallo(
                $usuario->id,
                $this->config['login_max_intentos'],
                $this->config['login_bloqueo_min']
            );
            throw new HttpException(self::MENSAJE_INVALIDO, 401);
        }

        $this->usuarios->registrarAcceso($usuario->id);

        $ahora = time();
        $expira = $ahora + $this->config['access_ttl_min'] * 60;
        $token = JwtUtils::generate([
            'sub' => $usuario->id,
            'usuario' => $usuario->usuario,
            'role' => $usuario->rol,
            'iat' => $ahora,
            'exp' => $expira,
        ]);

        return ['token' => $token, 'expira' => $expira, 'usuario' => $usuario->publico()];
    }

    /** Usuario de un token vigente; vuelve a consultar la BD por si lo desactivaron. */
    public function usuarioActual(int $id): array
    {
        $usuario = $this->usuarios->findById($id);
        if ($usuario === null || !$usuario->activo) {
            throw new HttpException('Sesión inválida', 401);
        }
        return $usuario->publico();
    }
}
