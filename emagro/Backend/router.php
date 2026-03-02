<?php
// router.php for PHP built-in web server
// This simulates the behavior of the Apache .htaccess file

// Allow static resources to be served directly
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|woff|woff2|ttf|eot|svg)$/', $_SERVER["REQUEST_URI"])) {
    return false;
}

// Route all other requests to the API entry point
require_once __DIR__ . '/api/v1/index.php';
