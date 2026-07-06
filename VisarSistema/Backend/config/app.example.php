<?php
/**
 * Plantilla de configuración de la aplicación.
 * Copiar este archivo como app.php y ajustar los valores.
 * app.php está excluido del repositorio (.gitignore).
 */
return [
    // Clave secreta para firmar tokens JWT.
    // Generar una clave aleatoria segura (mínimo 32 caracteres).
    'jwt_secret' => 'CAMBIAR_ESTA_CLAVE_POR_UNA_SEGURA',

    // Tiempo de expiración del token en segundos (por defecto 8 horas)
    'jwt_expiry' => 3600 * 8,
];
