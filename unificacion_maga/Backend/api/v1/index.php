<?php
// imports
use App\Core\Router;
use App\Controllers\ExampleController;

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

// 4. Initialize Router
$router = new Router();

// 5. Register Controllers manually 
$router->registerController(ExampleController::class);
$router->registerController(\App\Controllers\ActividadesDespacho\ActividadController::class);
$router->registerController(\App\Controllers\Votaciones\VotacionController::class);
$router->registerController(\App\Controllers\VISAR\VisarController::class);
$router->registerController(\App\Controllers\VISAR\VisarDashboardController::class);
$router->registerController(\App\Controllers\VISAR\ExportacionesController::class);
$router->registerController(\App\Controllers\VISAR\ImportacionesController::class);
$router->registerController(\App\Controllers\VISAR\LicenciasTransporteController::class);
$router->registerController(\App\Controllers\VISAR\LicenciasFitoController::class);
$router->registerController(\App\Controllers\VISAR\LibreVentaController::class);
$router->registerController(\App\Controllers\VISAN\VisanController::class);
$router->registerController(\App\Controllers\VIDER\ViderController::class);
$router->registerController(\App\Controllers\Productores\ProductorController::class);
$router->registerController(\App\Controllers\Extension\ExtensionController::class);
$router->registerController(\App\Controllers\Presupuesto\PresupuestoController::class);
$router->registerController(\App\Controllers\Auth\AuthController::class);
$router->registerController(\App\Controllers\Users\UserController::class);
$router->registerController(\App\Controllers\Dashboard\DashboardController::class);
$router->registerController(\App\Controllers\Settings\SettingsController::class);
$router->registerController(\App\Controllers\Notifications\NotificationController::class);
$router->registerController(\App\Controllers\Search\SearchController::class);
$router->registerController(\App\Controllers\Audit\AuditController::class);
$router->registerController(\App\Controllers\Clima\AlertaController::class);
$router->registerController(\App\Controllers\Clima\RegistroController::class);
$router->registerController(\App\Controllers\Clima\DashboardController::class);


// 6. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
