<?php
require_once __DIR__ . '/Backend/vendor/autoload.php';

use App\Repositories\ConcreteTripRepository;
use App\Utils\Database;

$repo = new ConcreteTripRepository();
try {
    $repo->create([
        'proyecto_id' => 1,
        'vehiculo_id' => 1,
        'piloto_id' => 1,
        'created_by' => 1,
        'producto' => 'Arena',
        'm3' => 20,
        'hora_planta' => date('H:i:s'),
        'lat_planta' => null,
        'lng_planta' => null
    ]);
    echo "Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
