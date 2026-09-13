<?php
// imports
use App\Core\Router;
use App\Controllers\ExampleController;
use App\Controllers\PersonnelController;
use App\Controllers\ProjectController;
use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Controllers\MachineryController;
use App\Controllers\InventoryController;
use App\Controllers\SupplierController;
use App\Controllers\FinanceController;
use App\Controllers\BankConciliationController;
use App\Controllers\CreditsController;
use App\Controllers\BudgetsController;
use App\Controllers\PayrollController;
use App\Controllers\MaintenanceController;
use App\Controllers\DocumentsController;
use App\Controllers\AlertsController;
use App\Controllers\ClientController;
use App\Controllers\ProjectIncomeController;
use App\Controllers\VehicleController;
use App\Controllers\VehicleLogController;
use App\Controllers\RecurrentController;
use App\Controllers\ConcreteControlController;
use App\Controllers\BudgetExtensionController;
use App\Controllers\PuestosController;
use App\Controllers\IncidentController;
use App\Controllers\HeavyTransportController;
use App\Controllers\SpecialMachineryController;
use App\Controllers\FuelRecordController;
use App\Controllers\MechanicRecordController;
use App\Controllers\ContractorController;
use App\Controllers\ViaticoController;
use App\Controllers\RoleController;
use App\Controllers\DashboardController;

// Backend/api/v1/index.php

// Disable HTML error output to keep JSON valid
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

date_default_timezone_set('America/Guatemala');

require_once __DIR__ . '/../../autoload.php';

// 2. Set Headers / CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'https://www.concretosdeoriente.com',
    'https://concretosdeoriente.com',
    'http://www.concretosdeoriente.com',
    'http://concretosdeoriente.com',
    'http://100.61.146.53',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173'
];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://www.concretosdeoriente.com");
}

header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-User-Name");

// 3. Handle Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 4. Initialize Router
$router = new Router();

// 5. Register Controllers manually 
$router->registerController(ExampleController::class);
$router->registerController(PersonnelController::class);
$router->registerController(ProjectController::class);
$router->registerController(UserController::class);
$router->registerController(AuthController::class);
$router->registerController(MachineryController::class);
$router->registerController(InventoryController::class);
$router->registerController(SupplierController::class);
$router->registerController(FinanceController::class);
$router->registerController(BankConciliationController::class);
$router->registerController(CreditsController::class);
$router->registerController(BudgetsController::class);
$router->registerController(PayrollController::class);
$router->registerController(MaintenanceController::class);
$router->registerController(DocumentsController::class);
$router->registerController(AlertsController::class);
$router->registerController(ClientController::class);
$router->registerController(ProjectIncomeController::class);
$router->registerController(VehicleController::class);
$router->registerController(VehicleLogController::class);
$router->registerController(RecurrentController::class);
$router->registerController(ConcreteControlController::class);
$router->registerController(BudgetExtensionController::class);
$router->registerController(PuestosController::class);
$router->registerController(IncidentController::class);
$router->registerController(HeavyTransportController::class);
$router->registerController(SpecialMachineryController::class);
$router->registerController(FuelRecordController::class);
$router->registerController(MechanicRecordController::class);
$router->registerController(ContractorController::class);
$router->registerController(ViaticoController::class);
$router->registerController(RoleController::class);
$router->registerController(DashboardController::class);




// 6. Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
