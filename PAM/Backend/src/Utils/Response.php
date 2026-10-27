<?php
namespace App\Utils;

class Response
{
    const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION;

    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, self::FLAGS);
        exit;
    }

    public static function success($data = [], $message = 'Success', $statusCode = 200)
    {
        self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function error($message = 'Error', $statusCode = 400, $errors = [])
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /** Cuerpo ya serializado (por ejemplo JSON comprimido) con sus propias cabeceras. */
    public static function raw(string $body, array $headers = [], int $statusCode = 200)
    {
        http_response_code($statusCode);
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        echo $body;
        exit;
    }

    /** Responde ya y deja que el script siga trabajando (importaciones largas). */
    public static function finishAndContinue($data, int $statusCode = 200): void
    {
        $body = json_encode($data, self::FLAGS);
        ignore_user_abort(true);
        set_time_limit(0);
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Connection: close');
        header('Content-Length: ' . strlen($body));
        echo $body;
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
