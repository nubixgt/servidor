<?php
require_once __DIR__ . '/Backend/autoload.php';

// Mock the database connection by using the actual connection
try {
    $repo = new \App\Repositories\Votaciones\CongresistaRepository();
    // Simulate empty filters
    $filters = ['search' => '', 'bloque_id' => ''];
    $data = $repo->getAll($filters);
    echo "getAll Success: " . count($data) . " records\n";
    
    $stats = $repo->getStats();
    print_r($stats);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}
