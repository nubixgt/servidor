<?php
namespace App\Core;

use App\Utils\JwtUtils;

abstract class Controller
{
    // Common controller logic helper methods can go here
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /** Payload del JWT de la petición (el Router ya validó el token en rutas con #[Authorize]). */
    protected function authUser(): array
    {
        $header = getallheaders()['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $header, $m)) {
            return JwtUtils::validate($m[1]) ?: [];
        }
        return [];
    }
}
