<?php
namespace Controllers;

use Core\HttpException;
use Core\Request;
use Core\Response;
use Models\UsuarioModel;
use Utils\JwtUtils;

class AuthController extends BaseController {
    public function login() {
        $in = Request::input();
        $usuario = trim((string)($in['usuario'] ?? ''));
        $password = (string)($in['password'] ?? '');
        if ($usuario === '' || $password === '') throw new HttpException('Usuario y contraseña son requeridos.', 400);

        $u = UsuarioModel::findByUsuario($usuario);
        // Mismo mensaje para usuario inexistente, inactivo o clave errónea (no revela cuál falló).
        if (!$u || !(int)$u['activo'] || !password_verify($password, $u['password_hash'])) {
            throw new HttpException('Credenciales inválidas.', 401);
        }
        UsuarioModel::tocarAcceso($u['id']);

        Response::json([
            'token' => JwtUtils::generateToken(['usuario_id' => (int)$u['id']]),
            'user' => $this->datosUsuario($u),
        ]);
    }

    public function me() {
        $sesion = $this->autenticar();
        $u = UsuarioModel::findByUsuario($sesion['usuario']);
        Response::json(['user' => $this->datosUsuario($u)]);
    }

    private function datosUsuario(array $u) {
        return [
            'id' => (int)$u['id'], 'usuario' => $u['usuario'], 'rol' => $u['rol'],
            'equipo_id' => $u['equipo_id'] === null ? null : (int)$u['equipo_id'], 'equipo_nombre' => $u['equipo_nombre'],
        ];
    }
}
