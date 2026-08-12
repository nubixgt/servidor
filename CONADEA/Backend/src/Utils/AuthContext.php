<?php
namespace App\Utils;

/**
 * Resuelve el usuario autenticado a partir del JWT — Router::validateToken()
 * hace lo mismo pero es privado y solo lo usa para chequear roles, no expone
 * el payload a los controllers. Los endpoints que necesitan saber "para cuál
 * usuario" (progreso) usan esto en vez de duplicar la lógica de permisos.
 */
class AuthContext
{
    public static function usuarioActual(): array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Missing Bearer Token']);
            exit;
        }

        $payload = JwtUtils::validate($matches[1]);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid or expired token']);
            exit;
        }

        return $payload;
    }

    public static function usuarioId(): int
    {
        return (int) self::usuarioActual()['sub'];
    }
}
