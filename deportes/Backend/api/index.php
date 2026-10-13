<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autocarga: Core\X, Controllers\X, Models\X, Utils\X -> src/<Espacio>/<X>.php
spl_autoload_register(function ($clase) {
    $archivo = __DIR__ . '/../src/' . str_replace('\\', '/', $clase) . '.php';
    if (file_exists($archivo)) require_once $archivo;
});

use Core\HttpException;
use Core\Response;
use Core\Router;

$router = new Router();

// Sesión
$router->add('POST', '/api/login', 'AuthController', 'login');
$router->add('GET', '/api/me', 'AuthController', 'me');

// Panel (admin y encargado): el servidor limita por rol — el encargado solo ve y toca su equipo.
$router->add('GET', '/api/panel/resumen', 'PanelController', 'resumen');

$router->add('GET', '/api/panel/equipos', 'EquiposController', 'lista');
$router->add('GET', '/api/panel/equipos/{id}', 'EquiposController', 'ver');
$router->add('POST', '/api/panel/equipos', 'EquiposController', 'crear');            // solo admin
$router->add('POST', '/api/panel/equipos/{id}', 'EquiposController', 'actualizar');  // multipart (fotos)
$router->add('DELETE', '/api/panel/equipos/{id}', 'EquiposController', 'eliminar');  // solo admin

$router->add('GET', '/api/panel/jugadores', 'JugadoresController', 'lista');
$router->add('GET', '/api/panel/jugadores/{id}', 'JugadoresController', 'ver');
$router->add('POST', '/api/panel/jugadores', 'JugadoresController', 'crear');
$router->add('POST', '/api/panel/jugadores/{id}', 'JugadoresController', 'actualizar'); // multipart (foto)
$router->add('PATCH', '/api/panel/jugadores/{id}/baja', 'JugadoresController', 'baja');
$router->add('PATCH', '/api/panel/jugadores/{id}/alta', 'JugadoresController', 'alta');
$router->add('DELETE', '/api/panel/jugadores/{id}', 'JugadoresController', 'eliminar'); // solo admin

$router->add('GET', '/api/panel/partidos', 'PartidosController', 'lista');
$router->add('GET', '/api/panel/partidos/{id}', 'PartidosController', 'ver');
$router->add('POST', '/api/panel/partidos', 'PartidosController', 'crear');
$router->add('PUT', '/api/panel/partidos/{id}', 'PartidosController', 'actualizar');
$router->add('DELETE', '/api/panel/partidos/{id}', 'PartidosController', 'eliminar'); // solo admin
$router->add('GET', '/api/panel/partidos/{id}/jugadores', 'PartidosController', 'jugadores');
$router->add('GET', '/api/panel/partidos/{id}/eventos', 'PartidosController', 'eventos');
$router->add('POST', '/api/panel/partidos/{id}/eventos', 'PartidosController', 'crearEvento');
$router->add('PUT', '/api/panel/partidos/{id}/eventos/{eid}', 'PartidosController', 'actualizarEvento');
$router->add('DELETE', '/api/panel/partidos/{id}/eventos/{eid}', 'PartidosController', 'eliminarEvento');

// Normaliza la URI (php -S local, cPanel con /deportes/Backend/api, o index.php directo)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace(['/deportes/Backend/api', '/api/index.php'], '', $uri);
$uri = preg_replace('#^/api#', '', $uri);
$uri = '/api' . (($uri === '' || $uri === '/') ? '' : $uri);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
} catch (HttpException $e) {
    Response::error($e->getMessage(), $e->getCode(), $e->fields);
} catch (Throwable $e) {
    error_log('[deportes] ' . $e);
    Response::error('Error interno del servidor.', 500);
}
