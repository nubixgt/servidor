<?php
namespace App\Core;

abstract class Controller
{
    // Common controller logic helper methods can go here
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // Helper method to get the current authenticated user's payload
    protected function getUser()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            // Need to require JwtUtils if not imported, but we can use fully qualified name
            return \App\Utils\JwtUtils::validate($token);
        }

        return null;
    }
}
