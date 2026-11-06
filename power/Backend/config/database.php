<?php
use App\Utils\Env;

return [
    'host' => Env::get('DB_HOST', 'localhost'),
    'dbname' => Env::get('DB_NAME', 'visionwe_PowerCoachAI'),
    'username' => Env::get('DB_USER', 'visionwe'),
    'password' => Env::get('DB_PASSWORD', 'Guate25#'),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
