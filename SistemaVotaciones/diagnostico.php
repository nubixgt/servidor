<?php
/**
 * DIAGNÓSTICO DEL SISTEMA
 * Ejecuta este archivo para verificar que todo esté configurado correctamente
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico del Sistema</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        .test { padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid; }
        .success { background: #d1fae5; border-color: #10b981; color: #065f46; }
        .warning { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
        .error { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
        .info { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .icon { font-size: 20px; margin-right: 10px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Diagnóstico del Sistema de Votaciones</h1>
";

// 1. Verificar PHP
echo "<div class='test info'>";
echo "<strong><span class='icon'>🐘</span>PHP</strong><br>";
echo "Versión: " . PHP_VERSION . "<br>";
echo "Sistema: " . PHP_OS;
echo "</div>";

// 2. Verificar Python
echo "<div class='test ";
$pythonFound = false;
$pythonCmd = null;
$pythonVersion = null;

foreach (['python3', 'python', 'py'] as $cmd) {
    $output = shell_exec("$cmd --version 2>&1");
    if ($output && stripos($output, 'python') !== false) {
        $pythonFound = true;
        $pythonCmd = $cmd;
        $pythonVersion = trim($output);
        break;
    }
}

if ($pythonFound) {
    echo "success'>";
    echo "<strong><span class='icon'>✅</span>Python</strong><br>";
    echo "Comando: <code>$pythonCmd</code><br>";
    echo "Versión: $pythonVersion";
} else {
    echo "error'>";
    echo "<strong><span class='icon'>❌</span>Python NO ENCONTRADO</strong><br>";
    echo "Necesitas instalar Python 3. Descárgalo de: <a href='https://www.python.org/downloads/' target='_blank'>python.org</a>";
}
echo "</div>";

// 3. Verificar pdfplumber
if ($pythonFound) {
    echo "<div class='test ";
    $pdfplumberOutput = shell_exec("$pythonCmd -m pip show pdfplumber 2>&1");
    
    if ($pdfplumberOutput && stripos($pdfplumberOutput, 'Name: pdfplumber') !== false) {
        echo "success'>";
        echo "<strong><span class='icon'>✅</span>pdfplumber</strong><br>";
        echo "Instalado correctamente<br>";
        
        // Extraer versión
        if (preg_match('/Version: (.+)/', $pdfplumberOutput, $matches)) {
            echo "Versión: " . trim($matches[1]);
        }
    } else {
        echo "error'>";
        echo "<strong><span class='icon'>❌</span>pdfplumber NO INSTALADO</strong><br>";
        echo "Instalar con: <code>$pythonCmd -m pip install pdfplumber</code>";
    }
    echo "</div>";
}

// 4. Verificar directorios
echo "<div class='test ";
$logsDir = __DIR__ . '/logs';
$tmpDir = $logsDir . '/tmp';
$uploadsDir = __DIR__ . '/uploads';

$directoriosOk = true;

if (!is_dir($logsDir)) {
    if (!mkdir($logsDir, 0777, true)) {
        $directoriosOk = false;
    }
}

if (!is_dir($tmpDir)) {
    if (!mkdir($tmpDir, 0777, true)) {
        $directoriosOk = false;
    }
}

if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0777, true)) {
        $directoriosOk = false;
    }
}

if ($directoriosOk && is_writable($logsDir) && is_writable($tmpDir)) {
    echo "success'>";
    echo "<strong><span class='icon'>✅</span>Directorios</strong><br>";
    echo "logs/: " . (is_writable($logsDir) ? 'Escribible' : 'No escribible') . "<br>";
    echo "logs/tmp/: " . (is_writable($tmpDir) ? 'Escribible' : 'No escribible') . "<br>";
    echo "uploads/: " . (is_writable($uploadsDir) ? 'Escribible' : 'No escribible');
} else {
    echo "error'>";
    echo "<strong><span class='icon'>❌</span>Directorios</strong><br>";
    echo "Hay problemas con los permisos de directorios";
}
echo "</div>";

// 5. Verificar procesar.php
echo "<div class='test ";
$procesarFile = __DIR__ . '/procesar.php';

if (file_exists($procesarFile)) {
    $content = file_get_contents($procesarFile);
    if (strpos($content, 'class ProcesadorCongreso') !== false) {
        echo "success'>";
        echo "<strong><span class='icon'>✅</span>procesar.php</strong><br>";
        echo "Archivo correcto encontrado";
    } else {
        echo "warning'>";
        echo "<strong><span class='icon'>⚠️</span>procesar.php</strong><br>";
        echo "El archivo existe pero puede tener una versión antigua";
    }
} else {
    echo "error'>";
    echo "<strong><span class='icon'>❌</span>procesar.php</strong><br>";
    echo "Archivo no encontrado. Asegúrate de copiar procesar.php al directorio raíz";
}
echo "</div>";

// 6. Verificar config.php
echo "<div class='test ";
$configFile = __DIR__ . '/config.php';

if (file_exists($configFile)) {
    echo "success'>";
    echo "<strong><span class='icon'>✅</span>config.php</strong><br>";
    echo "Archivo de configuración encontrado";
} else {
    echo "error'>";
    echo "<strong><span class='icon'>❌</span>config.php</strong><br>";
    echo "Archivo de configuración no encontrado";
}
echo "</div>";

// 7. Verificar base de datos
echo "<div class='test ";
if (file_exists($configFile)) {
    try {
        require_once $configFile;
        $db = getDB();
        
        // Verificar tablas
        $tables = ['eventos_votacion', 'congresistas', 'bloques', 'votos', 'resumen_eventos'];
        $tablesFound = 0;
        
        foreach ($tables as $table) {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->rowCount() > 0) {
                $tablesFound++;
            }
        }
        
        if ($tablesFound == count($tables)) {
            echo "success'>";
            echo "<strong><span class='icon'>✅</span>Base de Datos</strong><br>";
            echo "Conexión exitosa<br>";
            echo "Todas las tablas encontradas ($tablesFound/" . count($tables) . ")";
        } else {
            echo "warning'>";
            echo "<strong><span class='icon'>⚠️</span>Base de Datos</strong><br>";
            echo "Conexión exitosa<br>";
            echo "Tablas encontradas: $tablesFound/" . count($tables) . "<br>";
            echo "Puede que falten algunas tablas";
        }
    } catch (Exception $e) {
        echo "error'>";
        echo "<strong><span class='icon'>❌</span>Base de Datos</strong><br>";
        echo "Error de conexión: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "warning'>";
    echo "<strong><span class='icon'>⚠️</span>Base de Datos</strong><br>";
    echo "No se puede verificar sin config.php";
}
echo "</div>";

// Resumen final
echo "<h2>📋 Resumen</h2>";

$todoBien = $pythonFound && 
            ($pdfplumberOutput && stripos($pdfplumberOutput, 'Name: pdfplumber') !== false) &&
            $directoriosOk &&
            file_exists($procesarFile);

if ($todoBien) {
    echo "<div class='test success'>";
    echo "<strong><span class='icon'>🎉</span>¡SISTEMA LISTO!</strong><br>";
    echo "Todos los componentes están configurados correctamente.<br>";
    echo "Puedes procesar PDFs desde la interfaz web.";
    echo "</div>";
} else {
    echo "<div class='test warning'>";
    echo "<strong><span class='icon'>⚠️</span>ACCIÓN REQUERIDA</strong><br>";
    echo "Revisa los elementos marcados en rojo y corrígelos antes de usar el sistema.<br><br>";
    echo "<strong>Pasos recomendados:</strong><br>";
    
    if (!$pythonFound) {
        echo "1. Instala Python 3 desde <a href='https://www.python.org/downloads/' target='_blank'>python.org</a><br>";
        echo "   - Durante la instalación, marca la opción 'Add Python to PATH'<br>";
    }
    
    if ($pythonFound && !($pdfplumberOutput && stripos($pdfplumberOutput, 'Name: pdfplumber') !== false)) {
        echo "2. Abre CMD o PowerShell y ejecuta:<br>";
        echo "   <code>$pythonCmd -m pip install pdfplumber</code><br>";
    }
    
    if (!file_exists($procesarFile)) {
        echo "3. Copia el archivo procesar.php al directorio raíz del proyecto<br>";
    }
    
    echo "</div>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<p style='text-align: center; color: #64748b;'>Sistema de Votaciones del Congreso de Guatemala | Diagnóstico v1.0</p>";
echo "</div></body></html>";
?>