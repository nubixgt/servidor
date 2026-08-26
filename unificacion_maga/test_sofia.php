<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/Backend/autoload.php';

use App\Utils\Database;

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT nombre FROM votaciones_congresistas WHERE nombre LIKE '%sofia%' OR nombre LIKE '%hernandez%' LIMIT 10");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
