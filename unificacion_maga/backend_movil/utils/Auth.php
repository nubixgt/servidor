<?php
// backend_movil/utils/Auth.php
// Utilidades para autenticación y manejo de tokens

class Auth
{

    /**
     * Generar token simple (basado en tiempo y usuario)
     * Para producción, considerar usar JWT (JSON Web Tokens)
     */
    public static function generateToken($userId, $usuario)
    {
        $timestamp = time();
        $randomString = bin2hex(random_bytes(16));
        $data = $userId . '|' . $usuario . '|' . $timestamp . '|' . $randomString;

        // Crear hash del token
        $token = hash_hmac('sha256', $data, TOKEN_SECRET);

        // Combinar con timestamp para poder validar expiración
        return base64_encode($token . '|' . $timestamp . '|' . $userId);
    }

    /**
     * Validar token
     */
    public static function validateToken($token)
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);

            if (count($parts) !== 3) {
                return false;
            }

            list($hash, $timestamp, $userId) = $parts;

            // Verificar si el token ha expirado
            if ((time() - $timestamp) > TOKEN_EXPIRATION) {
                return false;
            }

            return [
                'valid' => true,
                'user_id' => $userId,
                'timestamp' => $timestamp
            ];

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener token del header Authorization
     */
    public static function getTokenFromHeader()
    {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];

            // Formato: "Bearer {token}"
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Verificar contraseña
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Hashear contraseña
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Middleware: Verificar autenticación
     * Retorna el user_id si es válido, de lo contrario termina con error
     */
    public static function requireAuth()
    {
        $token = self::getTokenFromHeader();

        if (!$token) {
            Response::unauthorized('Token no proporcionado');
        }

        $validation = self::validateToken($token);

        if (!$validation || !$validation['valid']) {
            Response::unauthorized('Token inválido o expirado');
        }

        return $validation['user_id'];
    }

    /**
     * Sanitizar datos de entrada
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }

        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
?>