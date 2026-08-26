<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'Backend/autoload.php';

use App\Controllers\Auth\AuthController;

$controller = new AuthController();
echo "AuthController created successfully.\n";

$service = new \App\Services\Auth\AuthService();
echo "AuthService created successfully.\n";

$repo = new \App\Repositories\Auth\UserRepository();
echo "UserRepository created successfully.\n";
