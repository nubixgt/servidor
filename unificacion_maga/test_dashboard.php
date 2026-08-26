<?php
require 'Backend/autoload.php';
try {
    $c = new \App\Controllers\Dashboard\DashboardController();
    $c->stats();
} catch(Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
