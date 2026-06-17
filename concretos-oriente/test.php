<?php
require_once __DIR__ . '/Backend/vendor/autoload.php';

use App\Repositories\ConcreteTripRepository;
use App\Repositories\InventoryItemRepository;

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $tripRepo = new ConcreteTripRepository();
    $inventoryRepo = new InventoryItemRepository();

    $data = [
        'proyecto_id' => 1,
        'vehiculo_id' => 1,
        'piloto_id' => 1,
        'producto' => 'Arena',
        'm3' => 20,
        'lat_planta' => null,
        'lng_planta' => null,
    ];

    $item = $inventoryRepo->findByNombre($data['producto']);
    echo "Item ID: " . $item['id'] . "\n";

    $inventoryRepo->updateStock($item['id'], $data['m3'], '-');
    echo "Stock updated\n";

    $data['created_by'] = 1;
    $tripId = $tripRepo->create($data);
    echo "Trip ID: " . $tripId . "\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
