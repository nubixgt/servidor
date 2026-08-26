<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../autoload.php';

try {
    $c = new App\Repositories\CursoRepository();
    $curso = $c->findById(3); 
    file_put_contents('test_out.txt', print_r($curso, true));
    echo "OK";
} catch (Throwable $e) {
    file_put_contents('test_out.txt', "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo "ERROR";
}
