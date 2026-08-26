<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular la conexión directa con credenciales del config
$config = require __DIR__ . '/Backend/config/database.php';

echo "=== TEST BUSQUEDA GLOBAL ===\n\n";
echo "Conectando a: {$config['host']} / {$config['dbname']}\n";

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conexión exitosa\n\n";

    // Test 1: Buscar congresistas
    echo "[TEST 1] Tablas de votaciones existentes:\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'votaciones%'")->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);

    // Test 2: Cuantos congresistas hay
    echo "\n[TEST 2] Total congresistas en la tabla:\n";
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM votaciones_congresistas")->fetchColumn();
        echo "Total: $count\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // Test 3: Muestra algunos nombres reales
    echo "\n[TEST 3] Primeros 5 congresistas en la BD:\n";
    try {
        $rows = $pdo->query("SELECT id, nombre FROM votaciones_congresistas LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        print_r($rows);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // Test 4: Búsqueda con LIKE
    echo "\n[TEST 4] Búsqueda LIKE '%sofia%':\n";
    try {
        $stmt = $pdo->prepare("SELECT id, nombre FROM votaciones_congresistas WHERE nombre LIKE :q LIMIT 5");
        $stmt->execute([':q' => '%sofia%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Resultados: " . count($rows) . "\n";
        print_r($rows);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // Test 5: Buscar municipios en productores
    echo "\n[TEST 5] Búsqueda municipio '%guatemala%' en productores:\n";
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, municipio, departamento FROM productores WHERE municipio LIKE :q OR departamento LIKE :q LIMIT 5");
        $stmt->execute([':q' => '%guatemala%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Resultados: " . count($rows) . "\n";
        print_r($rows);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // Test 6: Verificar la tabla visar_licencias existe
    echo "\n[TEST 6] Verificar tablas clave:\n";
    $checkTables = ['productores', 'votaciones_congresistas', 'votaciones_bloques', 'visar_licencias', 'visar_inspecciones', 'visan_entregas', 'extension_rural', 'usuarios'];
    foreach ($checkTables as $t) {
        try {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "  ✅ $t: $cnt registros\n";
        } catch (Exception $e) {
            echo "  ❌ $t: NO EXISTE\n";
        }
    }

    // Test 7: Tabla de usuarios correcta
    echo "\n[TEST 7] Tabla 'users' (la que usa el repositorio de búsqueda):\n";
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        echo "  ✅ users: $cnt registros\n";
    } catch (Exception $e) {
        echo "  ❌ users: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
