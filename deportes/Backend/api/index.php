<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/Router.php';

// Controllers
require_once __DIR__ . '/../src/Controllers/EquipoController.php';
require_once __DIR__ . '/../src/Controllers/JugadorController.php';
require_once __DIR__ . '/../src/Controllers/StatsController.php';

// Models
require_once __DIR__ . '/../src/Models/EquipoModel.php';
require_once __DIR__ . '/../src/Models/JugadorModel.php';

use Core\Router;

$router = new Router();

// Define routes
$router->add('GET', '/api/equipos', 'EquipoController', 'getAll');
$router->add('GET', '/api/equipos/{id}', 'EquipoController', 'getById');
$router->add('POST', '/api/equipos', 'EquipoController', 'create');

$router->add('GET', '/api/jugadores', 'JugadorController', 'getAll');
$router->add('POST', '/api/jugadores', 'JugadorController', 'create');

$router->add('GET', '/api/stats', 'StatsController', 'getStats');

// Get request URI and Method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Clean up URI for local php built-in server or cPanel
$uri = str_replace('/deportes/Backend/api', '', $uri);
$uri = str_replace('/api/index.php', '', $uri);
$uri = str_replace('/api', '', $uri);

if (empty($uri) || $uri === '/') {
    $uri = '';
}
$uri = '/api' . $uri;

$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($method, $uri);
