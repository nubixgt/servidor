<?php
// imports
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\AdminUserController;
use App\Controllers\ProfileController;
use App\Controllers\NotebookController;
use App\Controllers\CheckinController;
use App\Controllers\CoachController;
use App\Controllers\PlanController;
use App\Controllers\UsageController;
use App\Controllers\NotificationController;
use App\Controllers\MetricsController;
use App\Controllers\ExperimentController;
use App\Controllers\FeedbackController;
use App\Controllers\StrengthLogController;
use App\Controllers\LegalController;
use App\Controllers\ReferralController;

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
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
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
// ExampleController (GET/POST /example sin autenticación, plantilla del
// esqueleto) se retiró el 2026-09-14 antes de la Beta 2.0: no lo llamaba
// ninguna pantalla y exponía una ruta de escritura pública.
$router->registerController(AuthController::class);
$router->registerController(AdminUserController::class);
$router->registerController(ProfileController::class);
$router->registerController(NotebookController::class);
$router->registerController(CheckinController::class);
$router->registerController(CoachController::class);
$router->registerController(PlanController::class);
$router->registerController(UsageController::class);
$router->registerController(NotificationController::class);
$router->registerController(MetricsController::class);
$router->registerController(LegalController::class);
$router->registerController(ExperimentController::class);
$router->registerController(FeedbackController::class);
// El registro de lo que el atleta levanto en el gimnasio (Beta 2.1, Database/026).
$router->registerController(StrengthLogController::class);
$router->registerController(ReferralController::class);
// $router->registerController(YourController::class);


// 6. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
