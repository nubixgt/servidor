<?php
/**
 * Script de Verificación del Sistema
 * Ejecutar: http://localhost/congreso/verificar.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Verificación del Sistema</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
.check { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid; }
.success { background: #d1fae5; border-color: #10b981; color: #065f46; }
.error { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
.warning { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
.info { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
.icon { font-size: 20px; margin-right: 10px; }
.details { margin-top: 10px; font-size: 14px; color: #666; }
h2 { color: #334155; margin-top: 30px; }
</style></head><body><div class='container'>";

echo "<h1>🔍 Verificación del Sistema de Votaciones</h1>";
echo "<p>Versión 1.0.0 | " . date('d/m/Y H:i:s') . "</p>";

// Array para almacenar resultados
$errores = 0;
$advertencias = 0;

// 1. Verificar versión de PHP
echo "<h2>1. Entorno PHP</h2>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<div class='check success'><span class='icon'>✓</span><strong>PHP:</strong> Versión $phpVersion (Compatible)</div>";
} else {
    echo "<div class='check error'><span class='icon'>✗</span><strong>PHP:</strong> Versión $phpVersion (Requiere 7.4+)</div>";
    $errores++;
}

// 2. Verificar extensiones PHP
echo "<h2>2. Extensiones PHP Requeridas</h2>";

$extensiones = [
    'pdo' => 'PDO',
    'pdo_mysql' => 'PDO MySQL',
    'mbstring' => 'Multibyte String',
    'fileinfo' => 'File Info',
    'json' => 'JSON'
];

foreach ($extensiones as $ext => $nombre) {
    if (extension_loaded($ext)) {
        echo "<div class='check success'><span class='icon'>✓</span><strong>$nombre:</strong> Instalado</div>";
    } else {
        echo "<div class='check error'><span class='icon'>✗</span><strong>$nombre:</strong> NO instalado</div>";
        $errores++;
    }
}

// 3. Verificar archivos del sistema
echo "<h2>3. Archivos del Sistema</h2>";

$archivos = [
    'config.php' => 'Configuración',
    'database.sql' => 'Base de datos',
    'index.php' => 'Dashboard',
    'cargar.php' => 'Carga de PDFs',
    'procesar_pdf_simple.php' => 'Procesador Windows',
    'procesar_pdf.php' => 'Procesador completo'
];

foreach ($archivos as $archivo => $desc) {
    if (file_exists($archivo)) {
        echo "<div class='check success'><span class='icon'>✓</span><strong>$desc:</strong> $archivo existe</div>";
    } else {
        echo "<div class='check error'><span class='icon'>✗</span><strong>$desc:</strong> $archivo NO encontrado</div>";
        $errores++;
    }
}

// 4. Verificar directorio uploads
echo "<h2>4. Directorios</h2>";

$uploadDir = __DIR__ . '/uploads';
if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "<div class='check success'><span class='icon'>✓</span><strong>Uploads:</strong> Directorio existe y es escribible</div>";
    } else {
        echo "<div class='check warning'><span class='icon'>⚠</span><strong>Uploads:</strong> Directorio existe pero NO es escribible<br>";
        echo "<div class='details'>Solución: Dar permisos de escritura a la carpeta uploads/</div></div>";
        $advertencias++;
    }
} else {
    echo "<div class='check warning'><span class='icon'>⚠</span><strong>Uploads:</strong> Directorio NO existe<br>";
    echo "<div class='details'>Solución: Crear carpeta 'uploads' en el directorio del sistema</div></div>";
    $advertencias++;
}

// 5. Verificar conexión a base de datos
echo "<h2>5. Conexión a Base de Datos</h2>";

try {
    require_once 'config.php';
    $db = getDB();
    echo "<div class='check success'><span class='icon'>✓</span><strong>Conexión MySQL:</strong> Exitosa</div>";
    
    // Verificar tablas
    $tablas = ['eventos_votacion', 'congresistas', 'bloques', 'votos', 'resumen_eventos'];
    $tablasExisten = true;
    
    foreach ($tablas as $tabla) {
        $stmt = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() == 0) {
            $tablasExisten = false;
            echo "<div class='check error'><span class='icon'>✗</span><strong>Tabla $tabla:</strong> NO existe<br>";
            echo "<div class='details'>Solución: Importar database.sql en phpMyAdmin</div></div>";
            $errores++;
        }
    }
    
    if ($tablasExisten) {
        echo "<div class='check success'><span class='icon'>✓</span><strong>Estructura BD:</strong> Todas las tablas existen</div>";
        
        // Contar registros
        $stmt = $db->query("SELECT COUNT(*) as total FROM eventos_votacion");
        $eventos = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM congresistas");
        $congresistas = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM votos");
        $votos = $stmt->fetch()['total'];
        
        echo "<div class='check info'><span class='icon'>ℹ</span><strong>Datos actuales:</strong><br>";
        echo "<div class='details'>• $eventos eventos registrados<br>• $congresistas congresistas<br>• $votos votos almacenados</div></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='check error'><span class='icon'>✗</span><strong>Conexión MySQL:</strong> Error<br>";
    echo "<div class='details'>Error: " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "Soluciones:<br>";
    echo "1. Verificar que MySQL está corriendo<br>";
    echo "2. Verificar credenciales en config.php<br>";
    echo "3. Verificar que la base de datos 'congreso_votaciones' existe</div></div>";
    $errores++;
}

// 6. Detectar sistema operativo y procesador
echo "<h2>6. Procesador de PDFs</h2>";

$os = PHP_OS;
echo "<div class='check info'><span class='icon'>ℹ</span><strong>Sistema Operativo:</strong> $os</div>";

if (stripos(PHP_OS, 'WIN') === 0) {
    echo "<div class='check success'><span class='icon'>✓</span><strong>Procesador:</strong> Windows detectado - Usando procesador simple (no requiere Python)</div>";
} else {
    echo "<div class='check info'><span class='icon'>ℹ</span><strong>Procesador:</strong> Sistema Unix/Linux - Usando procesador completo</div>";
    
    // Verificar pdftotext
    exec('which pdftotext 2>&1', $output, $returnVar);
    if ($returnVar === 0) {
        echo "<div class='check success'><span class='icon'>✓</span><strong>pdftotext:</strong> Instalado</div>";
    } else {
        echo "<div class='check warning'><span class='icon'>⚠</span><strong>pdftotext:</strong> NO instalado (opcional)<br>";
        echo "<div class='details'>Instalar: sudo apt-get install poppler-utils</div></div>";
        $advertencias++;
    }
}

// 7. Límites PHP
echo "<h2>7. Configuración PHP</h2>";

$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$maxExecution = ini_get('max_execution_time');

echo "<div class='check info'><span class='icon'>ℹ</span><strong>Límites PHP:</strong><br>";
echo "<div class='details'>• Tamaño máximo de archivo: $uploadMax<br>• POST máximo: $postMax<br>• Tiempo de ejecución: $maxExecution segundos</div></div>";

if (intval($uploadMax) < 10) {
    echo "<div class='check warning'><span class='icon'>⚠</span><strong>Advertencia:</strong> upload_max_filesize es menor a 10MB<br>";
    echo "<div class='details'>Recomendado: Aumentar a 10M o más en php.ini</div></div>";
    $advertencias++;
}

// Resumen final
echo "<h2>📊 Resumen</h2>";

if ($errores == 0 && $advertencias == 0) {
    echo "<div class='check success' style='font-size: 18px;'>";
    echo "<span class='icon'>🎉</span><strong>¡Sistema Completamente Funcional!</strong><br>";
    echo "<div class='details' style='margin-top: 15px;'>Tu instalación está correcta y lista para usar.<br><br>";
    echo "Próximos pasos:<br>";
    echo "1. <a href='index.php'>Ir al Dashboard</a><br>";
    echo "2. <a href='cargar.php'>Cargar tu primer PDF</a></div></div>";
} else {
    if ($errores > 0) {
        echo "<div class='check error'><span class='icon'>✗</span><strong>$errores error(es) encontrado(s)</strong><br>";
        echo "<div class='details'>Debes corregir estos errores antes de usar el sistema</div></div>";
    }
    
    if ($advertencias > 0) {
        echo "<div class='check warning'><span class='icon'>⚠</span><strong>$advertencias advertencia(s)</strong><br>";
        echo "<div class='details'>El sistema puede funcionar, pero se recomienda resolver estas advertencias</div></div>";
    }
    
    echo "<div class='check info'><span class='icon'>📖</span><strong>¿Necesitas ayuda?</strong><br>";
    echo "<div class='details'>Consulta:<br>";
    echo "• INSTALACION_WINDOWS.md - Para Windows<br>";
    echo "• README.md - Documentación completa<br>";
    echo "• GUIA_RAPIDA.md - Guía de uso</div></div>";
}

echo "</div></body></html>";
?>
