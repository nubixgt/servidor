<?php

// Detección automática del entorno (local vs producción)
$isLocal = (str_contains(__DIR__, 'di3go') || 
            str_contains(__DIR__, 'OneDrive') || 
            (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')));

$username = $isLocal ? 'root' : 'visionwe';
$password = $isLocal ? '' : 'Guate25#';

return [
    'host'     => 'localhost',
    'dbname'   => 'visionwe_sigie',
    'username' => $username,
    'password' => $password,
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-06:00'"
    ]
];
