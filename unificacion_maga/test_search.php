<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/Backend/autoload.php';

use App\Repositories\Search\SearchRepository;

try {
    $repo = new SearchRepository();
    $results = $repo->globalSearch('San');
    echo json_encode(['status' => 'success', 'data' => $results], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
