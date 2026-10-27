<?php
use App\Utils\Env;

$storage = Env::get('STORAGE_DIR', __DIR__ . '/../storage');

return [
    'jwt_secret' => Env::get('JWT_SECRET', ''),
    'access_ttl_min' => (int) Env::get('ACCESS_TTL_MIN', '30'),
    'refresh_ttl_h' => (int) Env::get('REFRESH_TTL_H', '12'),
    'login_max_intentos' => (int) Env::get('LOGIN_MAX_INTENTOS', '5'),
    'login_bloqueo_min' => (int) Env::get('LOGIN_BLOQUEO_MIN', '15'),
    'busqueda_rpm' => (int) Env::get('BUSQUEDA_RPM', '90'),
    'cookie_secure' => Env::bool('COOKIE_SECURE', false),
    'cors_origins' => array_filter(array_map('trim', explode(',', Env::get('CORS_ORIGINS', 'http://localhost:5173')))),
    'entorno' => Env::get('ENTORNO', 'desarrollo'),
    'import_dir' => Env::get('IMPORT_DIR', $storage . '/importaciones'),
    'import_inline_mb' => (int) Env::get('IMPORT_INLINE_MB', '30'),
    'max_upload_mb' => (int) Env::get('MAX_UPLOAD_MB', '600'),
    'timezone' => 'America/Guatemala',
];
