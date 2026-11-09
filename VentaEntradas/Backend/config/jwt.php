<?php
// Clave secreta para firmar los tokens de sesión (JWT).
// En el SERVIDOR este archivo se crea a mano (el deploy no lo sube ni lo pisa).
// Usa una cadena larga y aleatoria, por ejemplo: openssl rand -hex 32
return [
    'secret' => '275d4d3048bad2491ff9dac8f3d1d0735a8c77732fc8d173d641951c3c0d974b',
    'ttl' => 60 * 60 * 12, // duración de la sesión: 12 horas
];
