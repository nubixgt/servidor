<?php
// imports
use App\Core\Router;
use App\Controllers\ExampleController;
use App\Controllers\AuthController;
use App\Controllers\UsuariosController;
use App\Controllers\PresupuestoController;
use App\Controllers\FiscalizacionController;
use App\Controllers\CalendarioController;
use App\Controllers\ModulosController;
use App\Controllers\DashboardController;
use App\Controllers\ArchivoController;

// Backend/api/v1/index.php

// 1. Load Autoloader
// Disable HTML error output to keep JSON valid
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🔍 DEBUG TEMPORAL - Borrar después
error_log(">>> REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log(">>> SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A'));
error_log(">>> METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));

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

// 4. Initialize Router
$router = new Router();

// 5. Register Controllers manually 
$router->registerController(ExampleController::class);
$router->registerController(AuthController::class);
$router->registerController(UsuariosController::class);
$router->registerController(PresupuestoController::class);
$router->registerController(FiscalizacionController::class);
$router->registerController(CalendarioController::class);
$router->registerController(ModulosController::class);
$router->registerController(DashboardController::class);
$router->registerController(ArchivoController::class);


// 6. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
