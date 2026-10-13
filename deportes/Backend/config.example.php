<?php
// Copia este archivo como config.local.php (NO se sube a git) y completa los valores reales.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'visionwe_deportes',
        'user' => 'visionwe_deportes',
        'pass' => 'CAMBIAR',
    ],
    // Cadena larga y aleatoria, p. ej.: php -r "echo bin2hex(random_bytes(32));"
    'jwt_secret' => 'CAMBIAR',
    // Duración de la sesión en segundos (7 días).
    'jwt_ttl' => 604800,
];
