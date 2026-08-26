<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../autoload.php';
require __DIR__ . '/../config/database.php'; 

try {
    $c = new App\Repositories\CursoRepository();
    $curso = $c->findById(3); 
    echo "OK";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}
