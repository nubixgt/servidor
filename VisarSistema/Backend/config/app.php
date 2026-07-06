<?php
/**
 * Configuración general de la aplicación.
 * IMPORTANTE: Este archivo NO debe subirse al repositorio.
 * Copiar app.example.php → app.php y ajustar los valores.
 */
return [
    // Clave secreta para firmar tokens JWT.
    // Debe ser una cadena larga y aleatoria. Cambiar en producción.
    'jwt_secret' => 'MAGA_JWT_SECRET_2026_NubixGT_$3cur3K3y!xQz9',

    // Tiempo de expiración del token en segundos (8 horas)
    'jwt_expiry' => 3600 * 8,
];
