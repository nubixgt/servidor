<?php
// Configuración de Seguridad JWT
return [
    'secret' => getenv('JWT_SECRET') ?: 'c7b489d2e1f56a9083b4c6e789a0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
    'algo'   => 'HS256',
    'ttl'    => 60 * 60 * 24 * 7 // 7 días de vigencia
];
