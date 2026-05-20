<?php

// ⚠️  ENTORNO LOCAL (XAMPP)
// Para producción, usa: username=visionwe, password=Guate25#
return [
    'host'     => 'localhost',
    'dbname'   => 'visionwe_SysDipu',
    'username' => 'visionwe',
    'password' => 'Guate25#',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-06:00'"
    ]
];
