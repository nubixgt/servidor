<?php
/**
 * Diagnostico de busqueda - ejecutar desde el servidor de produccion
 * URL: https://maga.nubix.gt/Backend/api/v1/diagnostico
 */
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../autoload.php';
use App\Utils\Database;

echo "=== DIAGNOSTICO BUSQUEDA - " . date('Y-m-d H:i:s') . " ===\n\n";

try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Conexion a BD exitosa\n\n";

    $param = '%lopez%';

    $tests = [
        '1. productores' => [
            'sql' => "SELECT id, nombre, apellido, municipio, departamento FROM productores WHERE nombre LIKE :q OR apellido LIKE :q OR municipio LIKE :q OR departamento LIKE :q LIMIT 3",
        ],
        '2. votaciones_congresistas (sin join)' => [
            'sql' => "SELECT id, nombre FROM votaciones_congresistas WHERE nombre LIKE :q LIMIT 3",
        ],
        '3. votaciones_congresistas (con JOIN bloques)' => [
            'sql' => "SELECT c.id, c.nombre, b.nombre as bloque, b.nombre_corto as partido FROM votaciones_congresistas c LEFT JOIN votaciones_bloques b ON c.bloque_id = b.id WHERE c.nombre LIKE :q OR b.nombre LIKE :q OR b.nombre_corto LIKE :q ORDER BY c.nombre ASC LIMIT 3",
        ],
        '4. votaciones_bloques' => [
            'sql' => "SELECT id, nombre, nombre_corto FROM votaciones_bloques WHERE nombre LIKE :q OR nombre_corto LIKE :q LIMIT 3",
        ],
        '5. votaciones_eventos' => [
            'sql' => "SELECT e.id, e.numero_evento, e.titulo FROM votaciones_eventos e WHERE e.titulo LIKE :q OR e.numero_evento LIKE :q LIMIT 3",
        ],
        '6. visar_licencias' => [
            'sql' => "SELECT id, titular, documento, tipo, estado FROM visar_licencias WHERE titular LIKE :q OR documento LIKE :q OR tipo LIKE :q LIMIT 3",
        ],
        '7. visar_inspecciones' => [
            'sql' => "SELECT id, nombre_empresa, tipo_inspeccion, departamento, municipio FROM visar_inspecciones WHERE nombre_empresa LIKE :q OR departamento LIKE :q OR municipio LIKE :q LIMIT 3",
        ],
        '8. visan_entregas' => [
            'sql' => "SELECT id, departamento, municipio, tipo_asistencia FROM visan_entregas WHERE departamento LIKE :q OR municipio LIKE :q OR tipo_asistencia LIKE :q LIMIT 3",
        ],
        '9. extension_rural' => [
            'sql' => "SELECT id, municipio, departamento, tipo_asistencia FROM extension_rural WHERE municipio LIKE :q OR departamento LIKE :q OR tipo_asistencia LIKE :q LIMIT 3",
        ],
        '10. tabla users' => [
            'sql' => "SELECT id, username, nombre_completo FROM users WHERE username LIKE :q OR nombre_completo LIKE :q LIMIT 3",
        ],
        '11. tabla usuarios' => [
            'sql' => "SELECT id, username, nombre_completo FROM usuarios WHERE username LIKE :q OR nombre_completo LIKE :q LIMIT 3",
        ],
    ];

    foreach ($tests as $label => $test) {
        try {
            $stmt = $pdo->prepare($test['sql']);
            $stmt->execute([':q' => $param]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($rows);
            echo "✅ $label → $count filas\n";
            if ($count > 0) {
                echo "   Ejemplo: " . json_encode($rows[0], JSON_UNESCAPED_UNICODE) . "\n";
            }
        } catch (Throwable $e) {
            echo "❌ $label → ERROR: " . $e->getMessage() . "\n";
        }
    }

    // Buscar hellen (nombre real que sabemos existe)
    echo "\n=== Buscar 'hellen' en congresistas ===\n";
    try {
        $stmt = $pdo->prepare("SELECT id, nombre FROM votaciones_congresistas WHERE nombre LIKE :q LIMIT 5");
        $stmt->execute([':q' => '%hellen%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo count($rows) . " resultados:\n";
        foreach ($rows as $r) echo "  - [{$r['id']}] {$r['nombre']}\n";
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }

    // Verificar columna bloque_id existe en congresistas
    echo "\n=== Columnas de votaciones_congresistas ===\n";
    $cols = $pdo->query("DESCRIBE votaciones_congresistas")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";

} catch (Throwable $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
