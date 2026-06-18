<?php
// Backend/api/v1/index.php

// Load Autoloader
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../autoload.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\InspectoresController;
use App\Controllers\CheckinController;
use App\Controllers\AnimalesController;
use App\Controllers\DesviacionesController;

// Set Headers / CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize Router
$router = new Router();

// Register Controllers
$router->registerController(AuthController::class);
$router->registerController(InspectoresController::class);
$router->registerController(CheckinController::class);
$router->registerController(AnimalesController::class);
$router->registerController(DesviacionesController::class);


// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
