<?php
// imports
use App\Core\HttpException;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\BeneficiarioController;
use App\Controllers\CatalogoController;
use App\Utils\Response;

// Backend/api/v1/index.php

// 1. Load Autoloader
// Disable HTML error output to keep JSON valid
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../autoload.php';

$config = require __DIR__ . '/../../config/app.php';
date_default_timezone_set($config['timezone']);

// 2. Set Headers / CORS
// Producción: frontend y API comparten dominio (no hace falta CORS). Desarrollo: solo los orígenes listados.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $config['cors_origins'], true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store");

// 3. Handle Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 4. Initialize Router
$router = new Router();

// 5. Register Controllers manually
$router->registerController(AuthController::class);
$router->registerController(CatalogoController::class);
$router->registerController(BeneficiarioController::class);

// 6. Dispatch
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpException $e) {
    Response::error($e->getMessage(), $e->status(), $e->errors());
} catch (\Throwable $e) {
    error_log($e);
    Response::error(
        $config['entorno'] === 'produccion' ? 'Error interno del servidor' : $e->getMessage(),
        500
    );
}
