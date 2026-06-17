<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/autoload.php';

$config = require __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Migración de Base de Datos - SIGIE</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0d131e; color: #e2e8f0; padding: 40px; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; background: #151f32; border: 1px solid #243552; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; border-bottom: 2px solid #243552; padding-bottom: 10px; margin-top: 0; }
        .status { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .success { background-color: #064e3b; color: #a7f3d0; border: 1px solid #047857; }
        .error { background-color: #7f1d1d; color: #fecaca; border: 1px solid #b91c1c; }
        .log-section { background: #0f172a; border: 1px solid #1e293b; border-radius: 6px; padding: 20px; font-family: 'Courier New', Courier, monospace; font-size: 14px; line-height: 1.6; overflow-x: auto; }
        .log-entry { margin-bottom: 8px; }
        .log-success { color: #4ade80; }
        .log-info { color: #38bdf8; }
        .log-warning { color: #fbbf24; }
        .log-error { color: #f87171; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Ejecutor de Migraciones SIGIE</h1>
";

$log = [];
function addLog($msg, $type = 'info') {
    global $log;
    $log[] = ['msg' => $msg, 'type' => $type];
}

try {
    // Connect to MySQL server first without selecting DB to ensure DB exists
    addLog("Conectando al servidor MySQL...", "info");
    $dsnNoDb = "mysql:host={$config['host']};charset={$config['charset']}";
    $pdo = new PDO($dsnNoDb, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database if not exists
    addLog("Creando base de datos '{$config['dbname']}' si no existe...", "info");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    addLog("Base de datos garantizada.", "success");

    // Reconnect with DB selected
    $db = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], $config['options']);
    addLog("Conexión a la base de datos '{$config['dbname']}' establecida con éxito.", "success");

    // Read schema.sql
    $schemaPath = __DIR__ . '/../Database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("schema.sql no encontrado en $schemaPath");
    }

    $sql = file_get_contents($schemaPath);
    addLog("Ejecutando schema.sql para configurar las tablas...", "info");
    $db->exec($sql);
    addLog("Tablas creadas exitosamente.", "success");

    // Helper function to seed users
    $seedUsersAndInspectors = function() use ($db) {
        $count = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        if ($count == 0) {
            addLog("Poblando usuarios y perfiles de inspectores iniciales...", "info");

            // 1. Create Admin
            $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
            $db->exec("INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, estado) VALUES ('Administrador General', 'admin', '$adminPass', 'administrador', 1)");
            addLog("Sembrado: Usuario Administrador ('admin' / 'admin123')", "success");

            // 2. Create Inspector 1
            $inspector1Pass = password_hash('inspector123', PASSWORD_BCRYPT);
            $db->exec("INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, estado) VALUES ('Juan Pérez', 'inspector1', '$inspector1Pass', 'inspector', 1)");
            $inspector1UserId = $db->lastInsertId();
            
            $db->exec("INSERT INTO inspectores (usuario_id, codigo, nombre, area, estado) VALUES ($inspector1UserId, 'INS-001', 'Juan Pérez', 'Sanidad Agroalimentaria', 1)");
            $inspector1Id = $db->lastInsertId();
            addLog("Sembrado: Inspector Juan Pérez ('inspector1' / 'inspector123')", "success");

            // 3. Create Inspector 2
            $inspector2Pass = password_hash('inspector123', PASSWORD_BCRYPT);
            $db->exec("INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, estado) VALUES ('María Gómez', 'inspector2', '$inspector2Pass', 'inspector', 1)");
            $inspector2UserId = $db->lastInsertId();
            
            $db->exec("INSERT INTO inspectores (usuario_id, codigo, nombre, area, estado) VALUES ($inspector2UserId, 'INS-002', 'María Gómez', 'Inocuidad de Alimentos', 1)");
            $inspector2Id = $db->lastInsertId();
            addLog("Sembrado: Inspector María Gómez ('inspector2' / 'inspector123')", "success");

            // 4. Seed Visitas / Inspecciones programadas
            addLog("Sembrando visitas de prueba asignadas a los inspectores...", "info");
            
            $visitas = [
                // Visitas para Juan Pérez (INS-001)
                [
                    ':inspector_id' => $inspector1Id,
                    ':establecimiento' => 'Restaurante El Portal',
                    ':direccion' => '5a Avenida 4-12, Zona 1, Guatemala',
                    ':fecha_programada' => date('Y-m-d'),
                    ':tipo_inspeccion' => 'Sanitaria de Rutina',
                    ':estado' => 'pendiente'
                ],
                [
                    ':inspector_id' => $inspector1Id,
                    ':establecimiento' => 'Supermercado La Torre',
                    ':direccion' => 'Calzada Roosevelt 10-33, Zona 11, Guatemala',
                    ':fecha_programada' => date('Y-m-d', strtotime('+1 day')),
                    ':tipo_inspeccion' => 'Auditoría de Inocuidad',
                    ':estado' => 'pendiente'
                ],
                [
                    ':inspector_id' => $inspector1Id,
                    ':establecimiento' => 'Distribuidora La Bendición',
                    ':direccion' => 'Terminal de Autobuses, Zona 4, Guatemala',
                    ':fecha_programada' => date('Y-m-d', strtotime('-2 days')),
                    ':tipo_inspeccion' => 'Verificación de Licencia',
                    ':estado' => 'pendiente'
                ],

                // Visitas para María Gómez (INS-002)
                [
                    ':inspector_id' => $inspector2Id,
                    ':establecimiento' => 'Farmacia La Paz',
                    ':direccion' => '10a Calle 7-50, Zona 9, Guatemala',
                    ':fecha_programada' => date('Y-m-d'),
                    ':tipo_inspeccion' => 'Inspección de Almacenamiento',
                    ':estado' => 'pendiente'
                ],
                [
                    ':inspector_id' => $inspector2Id,
                    ':establecimiento' => 'Panadería San Martín',
                    ':direccion' => 'Avenida Las Américas 15-20, Zona 13, Guatemala',
                    ':fecha_programada' => date('Y-m-d', strtotime('+2 days')),
                    ':tipo_inspeccion' => 'Control de Plagas y Limpieza',
                    ':estado' => 'pendiente'
                ]
            ];

            $stmt = $db->prepare("
                INSERT INTO visitas (inspector_id, establecimiento, direccion, fecha_programada, tipo_inspeccion, estado) 
                VALUES (:inspector_id, :establecimiento, :direccion, :fecha_programada, :tipo_inspeccion, :estado)
            ");
            foreach ($visitas as $v) {
                $stmt->execute($v);
            }
            addLog("Sembrado: 5 visitas de prueba creadas.", "success");
        } else {
            addLog("Tablas ya contienen usuarios. Omitiendo siembra.", "info");
        }
    };

    $seedUsersAndInspectors();

    // Ensure upload directories exist
    addLog("Verificando directorios de subida de archivos...", "info");
    $uploadsDir = __DIR__ . '/uploads';
    $checkinsDir = $uploadsDir . '/checkins';
    $firmasDir = $uploadsDir . '/firmas';

    foreach ([$uploadsDir, $checkinsDir, $firmasDir] as $dir) {
        if (!file_exists($dir)) {
            if (@mkdir($dir, 0777, true)) {
                addLog("Directorio creado: " . basename($dir), "success");
            } else {
                addLog("Error al crear directorio: " . basename($dir), "warning");
            }
        }
        if (file_exists($dir)) {
            @chmod($dir, 0777);
            if (is_writable($dir)) {
                addLog("Directorio listo para escritura: " . basename($dir), "success");
            } else {
                addLog("Advertencia: Directorio NO es escribible: " . basename($dir), "warning");
            }
        }
    }

    echo "<div class='status success'>¡MIGRACIÓN Y SEMBRADO COMPLETADO CON ÉXITO!</div>";

} catch (Exception $e) {
    addLog("ERROR CRÍTICO: " . $e->getMessage(), "error");
    echo "<div class='status error'>MIGRACIÓN FALLIDA</div>";
}

echo "<div class='log-section'>";
foreach ($log as $entry) {
    $class = 'log-info';
    if ($entry['type'] === 'success') $class = 'log-success';
    if ($entry['type'] === 'warning') $class = 'log-warning';
    if ($entry['type'] === 'error') $class = 'log-error';
    
    echo "<div class='log-entry'><span class='{$class}'>[" . strtoupper($entry['type']) . "]</span> " . htmlspecialchars($entry['msg']) . "</div>";
}
echo "</div>";

echo "
</div>
</body>
</html>
";
