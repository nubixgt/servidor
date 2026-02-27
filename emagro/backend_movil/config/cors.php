<?php
/**
 * Configuración de CORS (Cross-Origin Resource Sharing)
 * Permite que la aplicación Flutter se comunique con el backend
 */

// Permitir solicitudes desde cualquier origen (para desarrollo)
// En producción, cambia '*' por la URL específica de tu app
header('Access-Control-Allow-Origin: *');

// Métodos HTTP permitidos
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Headers permitidos
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Tipo de contenido JSON
header('Content-Type: application/json; charset=UTF-8');

// Manejar preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}