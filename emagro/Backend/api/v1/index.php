<?php
// imports
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\ClientController;
use App\Controllers\ProductController;
use App\Controllers\SaleController;
use App\Controllers\PaymentController;
use App\Controllers\DashboardController;

// Backend/api/v1/index.php

// 1. Load Autoloader
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../../autoload.php';

// 2. Set Headers / CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
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
$router->registerController(AuthController::class);
$router->registerController(UserController::class);
$router->registerController(ClientController::class);
$router->registerController(ProductController::class);
$router->registerController(SaleController::class);
$router->registerController(PaymentController::class);
$router->registerController(DashboardController::class);

// 6. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
