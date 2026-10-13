<?php
namespace Core;

class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public static function error($message, $statusCode = 400, $fields = []) {
        $cuerpo = ['error' => true, 'message' => $message];
        if (!empty($fields)) $cuerpo['fields'] = $fields;
        self::json($cuerpo, $statusCode);
    }
}
