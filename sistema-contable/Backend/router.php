<?php
// router.php
// Este archivo sirve como enrutador para el servidor integrado de PHP (php -S)
// para imitar el comportamiento de un servidor web real (como Apache o Nginx).

// 1. Obtener la ruta de la solicitud
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($url, '/');

// 2. Si el archivo físico realmente existe en el disco (ej. imagenes, css), sirvelo directo.
if ($path && file_exists(__DIR__ . '/' . $path)) {
    return false;
}

// 3. De lo contrario, redirige TODAS las peticiones hacia nuestro index principal de la API
$_SERVER['SCRIPT_NAME'] = '/api/v1/index.php';
require_once __DIR__ . '/api/v1/index.php';
