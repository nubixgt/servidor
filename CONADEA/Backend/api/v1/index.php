<?php
// imports
use App\Core\Router;
use App\Controllers\ExampleController;
use App\Controllers\AuthController;
use App\Controllers\LocationController;

// Backend/api/v1/index.php

// 1. Load Autoloader
// Disable HTML error output to keep JSON valid
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../autoload.php';

// 2. Set Headers / CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 3. Handle Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 4. TEMPORARY diagnostics — remove after fixing routing on this host.
// Usage: GET /?__debug=1 (through the rewritten Backend/ URL)
if (isset($_GET['__debug'])) {
    echo json_encode([
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
        'PHP_SELF' => $_SERVER['PHP_SELF'] ?? null,
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? null,
        'PATH_INFO' => $_SERVER['PATH_INFO'] ?? null,
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? null,
        'ORIG_PATH_INFO' => $_SERVER['ORIG_PATH_INFO'] ?? null,
    ]);
    exit;
}

// 5. Initialize Router
$router = new Router();

// 6. Register Controllers manually
$router->registerController(ExampleController::class);
$router->registerController(AuthController::class);
$router->registerController(LocationController::class);
// $router->registerController(YourController::class);


// 7. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
