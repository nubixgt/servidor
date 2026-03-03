<?php
/**
 * Verificador de Requisitos - VIDER
 * MAGA Guatemala
 * 
 * Ejecuta este archivo para diagnosticar problemas de configuración
 */

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación VIDER - MAGA</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
               padding: 2rem; max-width: 900px; margin: 0 auto; background: #f5f5f5; }
        h1 { color: #1a3a5c; }
        .check { padding: 1rem; margin: 0.5rem 0; border-radius: 8px; }
        .success { background: #d4edda; border: 1px solid #28a745; }
        .error { background: #f8d7da; border: 1px solid #dc3545; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; }
        code { background: #e9ecef; padding: 0.25rem 0.5rem; border-radius: 4px; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        .icon { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <h1>🔍 Verificación del Sistema VIDER</h1>';

$errors = [];
$warnings = [];
$baseDir = dirname(__FILE__);

// 1. PHP Version
echo '<h2>1. PHP</h2>';
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
echo '<div class="check ' . ($phpOk ? 'success' : 'error') . '">';
echo '<span class="icon">' . ($phpOk ? '✅' : '❌') . '</span>';
echo "PHP Version: <strong>$phpVersion</strong>";
if (!$phpOk) {
    echo ' (Se requiere PHP 7.4 o superior)';
    $errors[] = 'PHP version';
}
echo '</div>';

// 2. Extensiones PHP
echo '<h2>2. Extensiones PHP</h2>';
$extensions = [
    'pdo' => 'PDO (conexión a BD)',
    'pdo_mysql' => 'PDO MySQL',
    'zip' => 'ZIP (para Excel)',
    'xml' => 'XML (para Excel)',
    'mbstring' => 'Multibyte String',
    'fileinfo' => 'FileInfo (MIME types)'
];

foreach ($extensions as $ext => $name) {
    $loaded = extension_loaded($ext);
    echo '<div class="check ' . ($loaded ? 'success' : 'error') . '">';
    echo '<span class="icon">' . ($loaded ? '✅' : '❌') . '</span>';
    echo "$name: <strong>" . ($loaded ? 'Instalado' : 'NO INSTALADO') . '</strong>';
    echo '</div>';
    if (!$loaded)
        $errors[] = $ext;
}

// 3. Config.php
echo '<h2>3. Configuración</h2>';
$configPath = $baseDir . '/includes/config.php';

if (file_exists($configPath)) {
    echo '<div class="check success"><span class="icon">✅</span> config.php existe</div>';

    require_once $configPath;

    // Verificar constantes
    $constants = ['UPLOAD_PATH', 'LOG_PATH', 'MAX_FILE_SIZE', 'DB_HOST', 'DB_NAME'];
    foreach ($constants as $const) {
        $defined = defined($const);
        echo '<div class="check ' . ($defined ? 'success' : 'error') . '">';
        echo '<span class="icon">' . ($defined ? '✅' : '❌') . '</span>';
        echo "Constante <code>$const</code>: ";
        if ($defined) {
            $val = constant($const);
            if (strpos($const, 'PATH') !== false) {
                echo "<code>$val</code>";
            } else {
                echo '<strong>definida</strong>';
            }
        } else {
            echo '<strong>NO DEFINIDA</strong>';
            $errors[] = "Constante $const";
        }
        echo '</div>';
    }
} else {
    echo '<div class="check error"><span class="icon">❌</span> config.php NO EXISTE</div>';
    $errors[] = 'config.php';
}

// 4. Directorios
echo '<h2>4. Directorios y Permisos</h2>';

$directories = [
    'uploads' => defined('UPLOAD_PATH') ? UPLOAD_PATH : $baseDir . '/uploads/',
    'logs' => defined('LOG_PATH') ? LOG_PATH : $baseDir . '/logs/',
    'api' => $baseDir . '/api/',
    'vendor' => $baseDir . '/vendor/'
];

foreach ($directories as $name => $path) {
    $exists = file_exists($path) && is_dir($path);
    $writable = $exists && is_writable($path);

    if ($name === 'uploads' || $name === 'logs') {
        // Intentar crear si no existe
        if (!$exists) {
            @mkdir($path, 0755, true);
            $exists = file_exists($path) && is_dir($path);
            $writable = $exists && is_writable($path);
        }

        if (!$exists) {
            echo '<div class="check error">';
            echo '<span class="icon">❌</span>';
            echo "Directorio <code>$name</code>: <strong>NO EXISTE</strong> ($path)";
            echo '</div>';
            $errors[] = "Directorio $name";
        } elseif (!$writable) {
            echo '<div class="check error">';
            echo '<span class="icon">❌</span>';
            echo "Directorio <code>$name</code>: <strong>SIN PERMISOS DE ESCRITURA</strong>";
            echo '</div>';
            $errors[] = "Permisos $name";
        } else {
            echo '<div class="check success">';
            echo '<span class="icon">✅</span>';
            echo "Directorio <code>$name</code>: <strong>OK</strong> ($path)";
            echo '</div>';
        }
    } elseif ($name === 'vendor') {
        echo '<div class="check ' . ($exists ? 'success' : 'warning') . '">';
        echo '<span class="icon">' . ($exists ? '✅' : '⚠️') . '</span>';
        echo "Directorio <code>$name</code>: ";
        if (!$exists) {
            echo '<strong>NO EXISTE</strong> - Ejecutar: <code>composer require phpoffice/phpspreadsheet</code>';
            $warnings[] = "vendor";
        } else {
            echo '<strong>OK</strong>';
        }
        echo '</div>';
    } else {
        echo '<div class="check ' . ($exists ? 'success' : 'error') . '">';
        echo '<span class="icon">' . ($exists ? '✅' : '❌') . '</span>';
        echo "Directorio <code>$name</code>: <strong>" . ($exists ? 'OK' : 'NO EXISTE') . '</strong>';
        echo '</div>';
        if (!$exists)
            $errors[] = "Directorio $name";
    }
}

// 5. PhpSpreadsheet
echo '<h2>5. PhpSpreadsheet</h2>';
$autoloadPath = $baseDir . '/vendor/autoload.php';
$spreadsheetOk = false;

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    $spreadsheetOk = class_exists('PhpOffice\PhpSpreadsheet\IOFactory');
}

echo '<div class="check ' . ($spreadsheetOk ? 'success' : 'error') . '">';
echo '<span class="icon">' . ($spreadsheetOk ? '✅' : '❌') . '</span>';
echo 'PhpSpreadsheet: <strong>' . ($spreadsheetOk ? 'INSTALADO' : 'NO INSTALADO') . '</strong>';
if (!$spreadsheetOk) {
    echo '<br><br>Para instalar, ejecuta en terminal:';
    echo '<pre>cd ' . $baseDir . '
composer require phpoffice/phpspreadsheet</pre>';
    $errors[] = 'PhpSpreadsheet';
}
echo '</div>';

// 6. Conexión BD
echo '<h2>6. Base de Datos</h2>';

if (file_exists($configPath)) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        echo '<div class="check success">';
        echo '<span class="icon">✅</span>';
        echo 'Conexión a MySQL: <strong>OK</strong>';
        echo '</div>';

        // Verificar tablas
        $tables = ['importaciones', 'datos_vider', 'departamentos', 'municipios', 'dependencias'];
        foreach ($tables as $table) {
            try {
                $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
                $exists = !empty($result);
                echo '<div class="check ' . ($exists ? 'success' : 'warning') . '">';
                echo '<span class="icon">' . ($exists ? '✅' : '⚠️') . '</span>';
                echo "Tabla <code>$table</code>: ";
                echo '<strong>' . ($exists ? 'OK' : 'NO EXISTE') . '</strong>';
                echo '</div>';
                if (!$exists)
                    $warnings[] = "Tabla $table";
            } catch (Exception $e) {
                // ignorar
            }
        }

    } catch (Exception $e) {
        echo '<div class="check error">';
        echo '<span class="icon">❌</span>';
        echo 'Error de conexión: <strong>' . htmlspecialchars($e->getMessage()) . '</strong>';
        echo '</div>';
        $errors[] = 'Conexión BD';
    }
}

// 7. Configuración PHP para uploads
echo '<h2>7. Configuración PHP para Uploads</h2>';

$configs = [
    'upload_max_filesize' => ['value' => ini_get('upload_max_filesize'), 'min' => '50M'],
    'post_max_size' => ['value' => ini_get('post_max_size'), 'min' => '50M'],
    'max_execution_time' => ['value' => ini_get('max_execution_time'), 'min' => '120'],
    'memory_limit' => ['value' => ini_get('memory_limit'), 'min' => '128M']
];

foreach ($configs as $key => $data) {
    $value = $data['value'];
    $min = $data['min'];

    // Convertir a bytes para comparar
    $valueBytes = convertToBytes($value);
    $minBytes = convertToBytes($min);
    $ok = $valueBytes >= $minBytes || $value == -1 || $value == 0;

    echo '<div class="check ' . ($ok ? 'success' : 'warning') . '">';
    echo '<span class="icon">' . ($ok ? '✅' : '⚠️') . '</span>';
    echo "<code>$key</code>: <strong>$value</strong> (recomendado: $min)";
    echo '</div>';
}

function convertToBytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int) $val;
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

// 8. Archivos API
echo '<h2>8. Archivos del Sistema</h2>';

$files = [
    'api/upload.php' => 'API de subida de archivos',
    'api/process_import.php' => 'API de procesamiento',
    'includes/ExcelReader.php' => 'Lector de Excel'
];

foreach ($files as $file => $desc) {
    $path = $baseDir . '/' . $file;
    $exists = file_exists($path);
    echo '<div class="check ' . ($exists ? 'success' : 'error') . '">';
    echo '<span class="icon">' . ($exists ? '✅' : '❌') . '</span>';
    echo "$desc (<code>$file</code>): ";
    echo '<strong>' . ($exists ? 'OK' : 'NO EXISTE') . '</strong>';
    echo '</div>';
    if (!$exists)
        $errors[] = "Archivo $file";
}

// Resumen
echo '<h2>📊 Resumen</h2>';

if (empty($errors) && empty($warnings)) {
    echo '<div class="check success" style="font-size: 1.2rem;">';
    echo '<span class="icon">🎉</span>';
    echo '<strong>¡Todo está correctamente configurado!</strong>';
    echo '</div>';
} else {
    if (!empty($errors)) {
        echo '<div class="check error">';
        echo '<h3>❌ Errores críticos (' . count($errors) . '):</h3>';
        echo '<ul>';
        foreach ($errors as $err) {
            echo "<li>$err</li>";
        }
        echo '</ul>';
        echo '</div>';
    }

    if (!empty($warnings)) {
        echo '<div class="check warning">';
        echo '<h3>⚠️ Advertencias (' . count($warnings) . '):</h3>';
        echo '<ul>';
        foreach ($warnings as $warn) {
            echo "<li>$warn</li>";
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Instrucciones de solución
if (!empty($errors) || !empty($warnings)) {
    echo '<h2>🔧 Soluciones</h2>';

    if (in_array('PhpSpreadsheet', $errors)) {
        echo '<div class="check warning">';
        echo '<h4>Instalar PhpSpreadsheet:</h4>';
        echo '<pre>cd ' . $baseDir . '
composer require phpoffice/phpspreadsheet</pre>';
        echo '</div>';
    }

    if (in_array('Directorio uploads', $errors) || in_array('Permisos uploads', $errors)) {
        echo '<div class="check warning">';
        echo '<h4>Crear/Corregir directorio uploads:</h4>';
        echo '<pre>mkdir -p ' . (defined('UPLOAD_PATH') ? UPLOAD_PATH : $baseDir . '/uploads') . '
chmod 755 ' . (defined('UPLOAD_PATH') ? UPLOAD_PATH : $baseDir . '/uploads') . '</pre>';
        echo '</div>';
    }

    if (in_array('Conexión BD', $errors)) {
        echo '<div class="check warning">';
        echo '<h4>Verificar conexión a base de datos:</h4>';
        echo '<p>Edita <code>includes/config.php</code> y verifica las credenciales:</p>';
        echo '<pre>define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'vider_maga\');
define(\'DB_USER\', \'tu_usuario\');
define(\'DB_PASS\', \'tu_contraseña\');</pre>';
        echo '</div>';
    }

    $missingTables = array_filter($warnings, function ($w) {
        return strpos($w, 'Tabla') !== false;
    });

    if (!empty($missingTables)) {
        echo '<div class="check warning">';
        echo '<h4>Crear tablas de base de datos:</h4>';
        echo '<pre>mysql -u root -p vider_maga < database.sql</pre>';
        echo '<p>O importa <code>database.sql</code> desde phpMyAdmin</p>';
        echo '</div>';
    }
}

echo '</body></html>';
