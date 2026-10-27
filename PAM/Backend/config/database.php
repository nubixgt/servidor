<?php
use App\Utils\Env;

return [
    'host' => Env::get('DB_HOST', 'localhost'),
    'port' => Env::get('DB_PORT', '3306'),
    'dbname' => Env::get('DB_NAME', 'pam'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
