<?php
// router.php - Enrutador para el servidor de desarrollo integrado de PHP

if (php_sapi_name() == 'cli-server') {
    // Si la ruta solicitada es un archivo real (por ejemplo una imagen, css, etc.), sírvelo directamente
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        return false;
    }
    
    // Si el archivo no existe, simula el comportamiento de Apache (.htaccess)
    // Redirigiendo todo el tráfico a nuestro punto de entrada principal: api/v1/index.php
    $_SERVER['SCRIPT_NAME'] = '/api/v1/index.php';
    require __DIR__ . '/api/v1/index.php';
}
