<?php
// imports
use App\Core\Request;
use App\Core\Router;
use App\Controllers\SaludController;
use App\Controllers\AuthController;
use App\Controllers\CatalogoController;
use App\Controllers\TableroController;
use App\Controllers\ExpedienteController;
use App\Controllers\ReporteController;
use App\Controllers\ImportacionController;
use App\Controllers\AdminController;

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
// El frontend envía cookies (refresh token), así que el origen debe ser explícito, no "*"
$origin = Request::header('Origin');
if ($origin !== '' && in_array($origin, $config['cors_origins'], true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Request-ID");
header("Access-Control-Expose-Headers: Content-Disposition, ETag, X-Request-ID");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store");
header("X-Request-ID: " . Request::requestId());

// 3. Handle Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 4. Initialize Router
$router = new Router();

// 5. Register Controllers manually 
$router->registerController(SaludController::class);
$router->registerController(AuthController::class);
$router->registerController(CatalogoController::class);
$router->registerController(TableroController::class);
$router->registerController(ExpedienteController::class);
$router->registerController(ReporteController::class);
$router->registerController(ImportacionController::class);
$router->registerController(AdminController::class);
// $router->registerController(YourController::class);


// 6. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
