<?php
namespace App\Core;

use App\Entities\Usuario;

abstract class Controller
{
    // Common controller logic helper methods can go here
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION);
        exit;
    }

    /** Usuario autenticado (solo en rutas con #[Authorize] o #[HasPrivilege]). */
    protected function user(): Usuario
    {
        $user = Auth::user();
        if ($user === null) {
            throw new HttpException('Sesión requerida', 401);
        }
        return $user;
    }

    protected function body(): array
    {
        return Request::json();
    }

    protected function query(string $key, $default = null)
    {
        $value = $_GET[$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }
}
