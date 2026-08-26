<?php
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO DEL SISTEMA MAGA ===\n\n";

// 1. Check Backend Config & DB Connection
echo "[1] Conexión a Base de Datos:\n";
$configPath = __DIR__ . '/Backend/config/database.php';
if (!file_exists($configPath)) {
    echo "❌ Archivo de configuración no encontrado en $configPath\n";
} else {
    $config = require $configPath;
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Conexión exitosa a la base de datos '{$config['dbname']}'\n";
        
        // Count tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "   - Total de tablas en la base de datos: " . count($tables) . "\n";
        
        // Check users
        if (in_array('usuarios', $tables)) {
            $userCount = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            echo "   - Usuarios registrados: $userCount\n";
        } else {
            echo "   - ❌ Tabla 'usuarios' no encontrada.\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    }
}

echo "\n[2] Estado del Frontend:\n";
$frontendPath = __DIR__ . '/Frontend';
if (is_dir($frontendPath)) {
    echo "✅ Directorio Frontend encontrado.\n";
    if (file_exists($frontendPath . '/package.json')) {
        echo "   - package.json encontrado.\n";
    }
    if (is_dir($frontendPath . '/dist')) {
        echo "   - ✅ Build de producción (dist) existe.\n";
    } else {
        echo "   - ⚠️ Build de producción (dist) NO existe. (Requiere npm run build para producción)\n";
    }
} else {
    echo "❌ Directorio Frontend no encontrado.\n";
}

echo "\n[3] Permisos y Carpetas del Backend:\n";
$uploadsPath = __DIR__ . '/uploads';
if (is_dir($uploadsPath)) {
    echo "✅ Carpeta 'uploads' existe.\n";
    echo "   - Permisos de escritura: " . (is_writable($uploadsPath) ? "✅ Sí" : "❌ No") . "\n";
} else {
    echo "❌ Carpeta 'uploads' no encontrada.\n";
}

$backendLog = __DIR__ . '/Backend/logs';
if (!is_dir($backendLog)) {
    echo "⚠️ Carpeta 'Backend/logs' no encontrada (puede que no sea necesaria).\n";
}

echo "\n[4] Configuración de GitHub Actions:\n";
$workflows = __DIR__ . '/.github/workflows/deploy.yml';
if (file_exists($workflows)) {
    echo "✅ Archivo de despliegue (deploy.yml) encontrado.\n";
} else {
    echo "❌ Configuración de despliegue no encontrada.\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
