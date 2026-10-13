<?php
namespace Core;

class Request {
    // Cuerpo como arreglo: JSON o multipart/form-data (POST).
    public static function input() {
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($tipo, 'application/json') !== false) {
            $datos = json_decode(file_get_contents('php://input'), true);
            return is_array($datos) ? $datos : [];
        }
        return $_POST;
    }

    public static function query($clave, $default = null) {
        return $_GET[$clave] ?? $default;
    }
}
