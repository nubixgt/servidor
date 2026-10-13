<?php
namespace Core;

use Utils\JwtUtils;

class Auth {
    // Exige un token válido y que el usuario siga activo; devuelve sus datos actuales de la BD
    // (rol y equipo salen de la BD, no del token, para que un cambio de permisos aplique de inmediato).
    public static function require(array $roles) {
        $token = JwtUtils::getBearerToken();
        if (!$token) throw new HttpException('No autorizado. Token faltante.', 401);
        $payload = JwtUtils::verifyToken($token);
        if (!$payload || empty($payload['usuario_id'])) throw new HttpException('Sesión inválida o expirada.', 401);

        $stmt = Database::connection()->prepare('SELECT id, usuario, rol, equipo_id, activo FROM usuarios WHERE id = ?');
        $stmt->execute([$payload['usuario_id']]);
        $u = $stmt->fetch();
        if (!$u || !(int)$u['activo']) throw new HttpException('Sesión inválida o expirada.', 401);
        if (!in_array($u['rol'], $roles, true)) throw new HttpException('No tienes permisos para esta acción.', 403);

        return ['id' => (int)$u['id'], 'usuario' => $u['usuario'], 'rol' => $u['rol'], 'equipo_id' => $u['equipo_id'] === null ? null : (int)$u['equipo_id']];
    }
}
