<?php
namespace App\Services;

use App\Core\HttpException;
use App\Core\Request;
use App\DTOs\CambioClaveDTO;
use App\DTOs\LoginDTO;
use App\Entities\Usuario;
use App\Repositories\SesionRepository;
use App\Repositories\UsuarioRepository;
use App\Utils\JwtUtils;
use App\Utils\RateLimiter;
use App\Utils\Security;

class AuthService
{
    private $usuarios;
    private $sesiones;
    private $auditoria;
    private $config;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
        $this->sesiones = new SesionRepository();
        $this->auditoria = new AuditoriaService();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /** @return array{0: array, 1: string} respuesta JSON y refresh token para la cookie */
    public function login(LoginDTO $dto): array
    {
        RateLimiter::hit('login:' . (Request::ip() ?? '?'), 20);
        if ($dto->usuario === '' || $dto->clave === '') {
            throw new HttpException('Usuario o contraseña incorrectos', 401);
        }

        $r = $this->usuarios->buscarPorUsuario($dto->usuario);
        if ($r && $r['bloqueado']) {
            throw new HttpException("Usuario bloqueado temporalmente por intentos fallidos. Intente en {$r['minutos']} min.", 423);
        }
        if (!$r || !$r['activo'] || !Security::verificarClave($r['hash'], $dto->clave)) {
            if ($r) {
                $this->usuarios->registrarFallo((int) $r['id'], $this->config['login_max_intentos'], $this->config['login_bloqueo_min']);
            }
            $this->auditoria->registrar(null, 'LOGIN_FALLIDO', 'auth', 'seg.usuario', $r ? (string) $r['id'] : null, ['usuario' => mb_substr($dto->usuario, 0, 60)]);
            throw new HttpException('Usuario o contraseña incorrectos', 401);
        }

        $this->usuarios->registrarAcceso((int) $r['id']);
        $u = $this->usuarios->cargar((int) $r['id']);
        $u->ip = Request::ip();
        $u->requestId = Request::requestId();
        $this->auditoria->registrar($u, 'LOGIN', 'auth', 'seg.usuario', (string) $u->id);
        return $this->emitir($u);
    }

    /** @return array{0: array, 1: string} */
    public function refresh(?string $refreshToken): array
    {
        if (!$refreshToken) {
            throw new HttpException('Sin sesión', 401);
        }
        $usuarioId = $this->sesiones->consumirRefresh(Security::hashRefresh($refreshToken));
        if ($usuarioId === null) {
            throw new HttpException('La sesión expiró; ingrese de nuevo', 401);
        }
        $u = $this->usuarios->cargar($usuarioId);
        if (!$u) {
            throw new HttpException('Usuario inactivo', 401);
        }
        $u->ip = Request::ip();
        $u->requestId = Request::requestId();
        return $this->emitir($u);
    }

    public function logout(?string $refreshToken): void
    {
        if ($refreshToken) {
            $this->sesiones->cerrar(Security::hashRefresh($refreshToken));
        }
    }

    public function cambiarClave(Usuario $u, CambioClaveDTO $dto): void
    {
        $hash = $this->usuarios->hashDe($u->id);
        if (!$hash || !Security::verificarClave($hash, $dto->actual)) {
            throw new HttpException('La contraseña actual no es correcta', 400);
        }
        $problema = Security::validarClave($dto->nueva);
        if ($problema) {
            throw new HttpException($problema, 400);
        }
        if ($dto->nueva === $dto->actual) {
            throw new HttpException('La nueva contraseña debe ser distinta de la actual', 400);
        }
        $this->usuarios->cambiarClave($u->id, Security::hashClave($dto->nueva));
        $this->sesiones->revocarOtras($u->id, $u->sesionId);
        $this->auditoria->registrar($u, 'CAMBIO_CLAVE', 'auth', 'seg.usuario', (string) $u->id);
    }

    /** Usuario de un access token válido, o null si la sesión se cerró o el usuario está inactivo. */
    public function usuarioDesdeSesion(int $usuarioId, ?string $sesionId): ?Usuario
    {
        return $this->usuarios->cargar($usuarioId, $sesionId);
    }

    private function emitir(Usuario $u): array
    {
        [$refresh, $hash] = Security::nuevoRefresh();
        $sid = Security::uuid();
        $this->sesiones->crear($sid, $u->id, $hash, $u->ip, Request::userAgent(), $this->config['refresh_ttl_h']);
        $u->sesionId = $sid;

        $ahora = time();
        $access = JwtUtils::generate([
            'sub' => (string) $u->id,
            'sid' => $sid,
            'iat' => $ahora,
            'exp' => $ahora + $this->config['access_ttl_min'] * 60,
            'typ' => 'access',
        ]);

        return [[
            'access_token' => $access,
            'expira_en_s' => $this->config['access_ttl_min'] * 60,
            'usuario' => $u->toArray(),
        ], $refresh];
    }
}
