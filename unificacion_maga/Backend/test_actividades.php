<?php
require 'autoload.php';

use App\Repositories\ActividadesDespacho\ActividadRepository;

try {
    $r = new ActividadRepository();
    
    echo "Testing getAll():\n";
    $actividades = $r->getAll();
    print_r($actividades);
    
    echo "\nTesting getStatsByCategory():\n";
    print_r($r->getStatsByCategory());
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
