<?php
use App\Utils\Env;

return [
    'jwt_secret' => Env::get('JWT_SECRET', ''),
    'access_ttl_min' => (int) Env::get('ACCESS_TTL_MIN', '480'),
    'login_max_intentos' => (int) Env::get('LOGIN_MAX_INTENTOS', '5'),
    'login_bloqueo_min' => (int) Env::get('LOGIN_BLOQUEO_MIN', '15'),
    'cors_origins' => array_filter(array_map('trim', explode(',', Env::get('CORS_ORIGINS', 'http://localhost:5173')))),
    'entorno' => Env::get('ENTORNO', 'desarrollo'),
    'uploads_dir' => Env::get('UPLOADS_DIR', __DIR__ . '/../uploads/adjuntos'),
    'max_imagen_mb' => (int) Env::get('MAX_IMAGEN_MB', '5'),
    'timezone' => 'America/Guatemala',
];
