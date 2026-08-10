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
    $routerPath = __DIR__ . '/../../src/Core/Router.php';
    $routerSrc = file_get_contents($routerPath);
    preg_match('/\$scriptDir\s*=\s*dirname\([^\n]*/', $routerSrc, $m);

    $uri = strtok($_SERVER['REQUEST_URI'], '?');
    $scriptName = $_SERVER['SCRIPT_NAME'];

    echo json_encode([
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
        'uri_sin_query' => $uri,
        'scriptDir_formula_vieja_dirname_x1' => dirname($scriptName),
        'scriptDir_formula_nueva_dirname_x2' => dirname(dirname($scriptName)),
        'router_deployado_linea_scriptDir' => $m[0] ?? 'NO ENCONTRADA EN EL ARCHIVO',
        'router_php_mtime' => date('Y-m-d H:i:s', filemtime($routerPath)),
        'router_php_md5' => md5($routerSrc),
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
