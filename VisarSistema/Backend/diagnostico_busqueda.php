<?php
// Script de diagnostico - colocarlo en el servidor via FTP/SSH y ejecutar via browser
// URL: https://maga.nubix.gt/Backend/diagnostico_busqueda.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/autoload.php';
use App\Utils\Database;

echo "=== DIAGNOSTICO BUSQUEDA EN PRODUCCION ===\n\n";

try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Conexion a BD exitosa\n\n";

    $queries = [
        '1. productores (nombre LIKE lopez)' =>
            "SELECT COUNT(*) FROM productores WHERE nombre LIKE '%lopez%'",
        '2. votaciones_congresistas (nombre LIKE lopez)' =>
            "SELECT COUNT(*) FROM votaciones_congresistas WHERE nombre LIKE '%lopez%'",
        '3. votaciones_congresistas JOIN bloques' =>
            "SELECT c.nombre, b.nombre_corto FROM votaciones_congresistas c
             LEFT JOIN votaciones_bloques b ON c.bloque_id = b.id
             WHERE c.nombre LIKE '%lopez%' LIMIT 1",
        '4. visar_licencias' =>
            "SELECT COUNT(*) FROM visar_licencias",
        '5. visar_inspecciones' =>
            "SELECT COUNT(*) FROM visar_inspecciones",
        '6. visan_entregas' =>
            "SELECT COUNT(*) FROM visan_entregas",
        '7. extension_rural' =>
            "SELECT COUNT(*) FROM extension_rural",
        '8. tabla users (SearchRepository usa esta)' =>
            "SELECT COUNT(*) FROM users",
        '9. tabla usuarios (real)' =>
            "SELECT COUNT(*) FROM usuarios",
    ];

    foreach ($queries as $label => $sql) {
        try {
            $result = $pdo->query($sql)->fetchColumn();
            echo "  ✅ $label → $result\n";
        } catch (Throwable $e) {
            echo "  ❌ $label → " . $e->getMessage() . "\n";
        }
    }

    // Mostrar el resultado real de la busqueda
    echo "\n=== Simular SearchRepository::globalSearch('lopez') ===\n";
    $param = '%lopez%';
    $results = [];

    $tables = [
        'productores'                => "SELECT 'Productor' as type, CONCAT(nombre,' ',apellido) as name FROM productores WHERE nombre LIKE :q OR apellido LIKE :q LIMIT 3",
        'votaciones_congresistas'    => "SELECT 'Congresista' as type, nombre as name FROM votaciones_congresistas WHERE nombre LIKE :q LIMIT 3",
        'extension_rural municipio'  => "SELECT 'Extension' as type, municipio as name FROM extension_rural WHERE municipio LIKE :q LIMIT 3",
    ];

    foreach ($tables as $label => $sql) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':q' => $param]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\n  [$label]: " . count($rows) . " resultados\n";
            foreach ($rows as $r) {
                echo "    - [{$r['type']}] {$r['name']}\n";
            }
        } catch (Throwable $e) {
            echo "\n  [$label]: ERROR → " . $e->getMessage() . "\n";
        }
    }

    // Ver la estructura real del JOIN congresistas+bloques
    echo "\n=== Estructura tabla votaciones_congresistas ===\n";
    try {
        $cols = $pdo->query("DESCRIBE votaciones_congresistas")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";
    } catch (Throwable $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }

} catch (Throwable $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
