<?php
// Diagnostico: ejecutar cada consulta del SearchRepository individualmente y ver que falla
$config = [
    'host'     => 'srv1561.hstgr.io',
    'dbname'   => 'u991565456_maga_un',
    'username' => 'u991565456_maga_un',
    'password' => 'NR4bWu~u7B&o',
    'charset'  => 'utf8mb4',
];

echo "=== DIAGNOSTICO CONSULTAS DE BUSQUEDA EN PRODUCCION ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conexion exitosa\n\n";

    $queries = [
        '1. productores (nombre)'          => "SELECT COUNT(*) FROM productores WHERE nombre LIKE '%lopez%'",
        '2. votaciones_congresistas (nombre)' => "SELECT COUNT(*) FROM votaciones_congresistas WHERE nombre LIKE '%lopez%'",
        '3. votaciones_bloques'             => "SELECT COUNT(*) FROM votaciones_bloques WHERE nombre LIKE '%union%'",
        '4. votaciones_eventos'             => "SELECT COUNT(*) FROM votaciones_eventos LIMIT 1",
        '5. visar_licencias'                => "SELECT COUNT(*) FROM visar_licencias LIMIT 1",
        '6. visar_inspecciones'             => "SELECT COUNT(*) FROM visar_inspecciones LIMIT 1",
        '7. visan_entregas'                 => "SELECT COUNT(*) FROM visan_entregas LIMIT 1",
        '8. extension_rural'                => "SELECT COUNT(*) FROM extension_rural LIMIT 1",
        '9. users (tabla SearchRepo)'       => "SELECT COUNT(*) FROM users LIMIT 1",
        '10. usuarios (tabla real)'         => "SELECT COUNT(*) FROM usuarios LIMIT 1",
    ];

    foreach ($queries as $label => $sql) {
        try {
            $result = $pdo->query($sql)->fetchColumn();
            echo "  ✅ $label → $result\n";
        } catch (Exception $e) {
            echo "  ❌ $label → ERROR: " . $e->getMessage() . "\n";
        }
    }

    // Mostrar las tablas que tienen 'user' en el nombre
    echo "\n=== Tablas con 'user' en el nombre: ===\n";
    $tables = $pdo->query("SHOW TABLES LIKE '%user%'")->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);

    // Mostrar primeros 3 nombres de congresistas que contengan letras comunes
    echo "\n=== Muestra de congresistas (para saber formato real): ===\n";
    $rows = $pdo->query("SELECT nombre FROM votaciones_congresistas LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $r) echo "  - $r\n";

    // Probar JOIN con bloques que es el que busca por nombre
    echo "\n=== JOIN congresistas + bloques (como en SearchRepository): ===\n";
    try {
        $stmt = $pdo->prepare(
            "SELECT c.id, c.nombre, b.nombre as bloque, b.nombre_corto as partido
             FROM votaciones_congresistas c
             LEFT JOIN votaciones_bloques b ON c.bloque_id = b.id
             WHERE c.nombre LIKE :q OR b.nombre LIKE :q OR b.nombre_corto LIKE :q
             ORDER BY c.nombre ASC LIMIT 3"
        );
        $stmt->execute([':q' => '%lopez%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo count($rows) . " resultados para 'lopez':\n";
        print_r($rows);
    } catch (Exception $e) {
        echo "❌ Error JOIN: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "❌ Error de conexion: " . $e->getMessage() . "\n";
}
