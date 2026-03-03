<?php
namespace App\Core;

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
}
