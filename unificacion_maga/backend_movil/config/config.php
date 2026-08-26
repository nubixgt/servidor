<?php
// backend_movil/config/config.php
// Configuración general del backend

// Configurar zona horaria de Guatemala (GMT-6)
date_default_timezone_set('America/Guatemala');

// Configuración de errores (cambiar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al cliente
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Headers CORS para permitir peticiones desde Flutter
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Manejar peticiones OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de sesiones
define('SESSION_LIFETIME', 86400); // 24 horas en segundos
define('SESSION_NAME', 'APPCLIMA_SESSION');

// Configuración de tokens (para autenticación simple)
define('TOKEN_SECRET', 'AppClima_Secret_Key_2024_MAGA_Guatemala'); // Cambiar en producción
define('TOKEN_EXPIRATION', 86400); // 24 horas

// Roles permitidos en la app móvil
define('ALLOWED_ROLES', ['Tecnico']);

// Estados de usuario permitidos
define('ALLOWED_STATES', ['Activo']);

// Incluir clase de base de datos
require_once __DIR__ . '/Database.php';
?>