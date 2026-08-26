<?php
require_once __DIR__ . '/../Backend/config/database.php';
require_once __DIR__ . '/../Backend/src/Utils/Database.php';

use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "--- Importaciones ---\n";
    $stmt = $db->query("SELECT fecha_emision FROM visar_importaciones LIMIT 5");
    $imp_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($imp_dates);

    echo "--- Exportaciones ---\n";
    $stmt = $db->query("SELECT fecha_emision FROM visar_exportaciones LIMIT 5");
    $exp_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($exp_dates);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
