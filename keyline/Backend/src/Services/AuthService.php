<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\DTOs\LoginDTO;
use App\Utils\JwtUtils;
use App\Entities\Usuario;

class AuthService
{
    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    public function login(LoginDTO $dto): array
    {
        if ($dto->usuario === '' || $dto->password === '') {
            throw new \Exception('Usuario y contraseña son requeridos.');
        }

        $user = $this->usuarios->findByUsuario($dto->usuario);
        if (!$user || !$user->activo) {
            throw new \Exception('Credenciales inválidas o usuario inactivo.');
        }
        if (!password_verify($dto->password, $user->passwordHash)) {
            throw new \Exception('Credenciales inválidas.');
        }

        $this->usuarios->actualizarUltimoAcceso($user->id);

        $token = JwtUtils::generate([
            'id' => $user->id,
            'role' => $user->role,
            'nombre' => $user->nombre,
            'usuario' => $user->usuario,
            'regionAsignada' => $user->regionAsignada,
            'exp' => time() + (12 * 60 * 60),
        ]);

        return ['user' => $user->toPublicArray(), 'token' => $token];
    }

    public function me(): array
    {
        $payload = self::currentPayload();
        $user = $this->usuarios->findById((int)$payload['id']);
        if (!$user || !$user->activo) {
            throw new \Exception('Sesión inválida.');
        }
        return $user->toPublicArray();
    }

    public function cambiarPassword(string $actual, string $nueva): void
    {
        if (strlen($nueva) < 6) {
            throw new \Exception('La nueva contraseña debe tener al menos 6 caracteres.', 400);
        }
        $payload = self::currentPayload();
        $user = $this->usuarios->findById((int)$payload['id']);
        if (!$user || !password_verify($actual, $user->passwordHash)) {
            throw new \Exception('La contraseña actual no es correcta.', 400);
        }
        $this->usuarios->update($user->id, ['passwordHash' => password_hash($nueva, PASSWORD_BCRYPT)]);
    }

    /**
     * Extrae y valida el payload del JWT del header Authorization.
     * El Router ya validó la firma/expiración para rutas con #[Authorize],
     * esto solo vuelve a leer las claims para poder aplicar scoping por rol.
     */
    public static function currentPayload(): array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autenticado.']);
            exit;
        }
        $payload = JwtUtils::validate($matches[1]);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Sesión expirada o inválida.']);
            exit;
        }
        return $payload;
    }
}
