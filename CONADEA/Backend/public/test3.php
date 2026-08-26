<?php
require __DIR__ . '/../autoload.php';

try {
    $c = new App\Repositories\CursoRepository();
    $curso = $c->findById(3); 
    echo json_encode(['status' => 'success', 'data' => $curso]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
