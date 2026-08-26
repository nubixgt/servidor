<?php
// backend_movil/utils/Response.php
// Clase helper para respuestas JSON estandarizadas

class Response
{

    /**
     * Respuesta exitosa
     */
    public static function success($data = null, $message = 'Operación exitosa', $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Respuesta de error
     */
    public static function error($message = 'Error en la operación', $code = 400, $details = null)
    {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Respuesta no autorizado (401)
     */
    public static function unauthorized($message = 'No autorizado')
    {
        self::error($message, 401);
    }

    /**
     * Respuesta prohibido (403)
     */
    public static function forbidden($message = 'Acceso denegado')
    {
        self::error($message, 403);
    }

    /**
     * Respuesta no encontrado (404)
     */
    public static function notFound($message = 'Recurso no encontrado')
    {
        self::error($message, 404);
    }

    /**
     * Respuesta error del servidor (500)
     */
    public static function serverError($message = 'Error interno del servidor')
    {
        self::error($message, 500);
    }

    /**
     * Validar datos requeridos
     */
    public static function validateRequired($data, $requiredFields)
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            self::error(
                'Campos requeridos faltantes',
                400,
                ['missing_fields' => $missing]
            );
        }

        return true;
    }
}
?>