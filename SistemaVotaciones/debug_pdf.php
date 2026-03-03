<?php
/**
 * Debug del Procesador de PDF
 * Muestra paso a paso qué está extrayendo del PDF
 * Usar: http://localhost/congreso/debug_pdf.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'procesar_pdf_mejorado.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Debug PDF</title>";
echo "<style>
body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
h2 { color: #4ec9b0; border-bottom: 2px solid #4ec9b0; padding-bottom: 5px; }
.section { background: #252526; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 3px solid #007acc; }
.success { color: #4ec9b0; }
.error { color: #f48771; }
.warning { color: #ce9178; }
pre { background: #1e1e1e; padding: 10px; border: 1px solid #3c3c3c; overflow-x: auto; }
.voto { background: #2d2d30; padding: 8px; margin: 5px 0; border-left: 3px solid #007acc; }
.btn { display: inline-block; padding: 10px 20px; background: #007acc; color: white; text-decoration: none; border-radius: 3px; margin: 10px 0; }
.btn:hover { background: #005a9e; }
</style></head><body>";

echo "<h1>🔍 Debug del Procesador de PDF</h1>";

// Buscar PDF en uploads
$uploadDir = __DIR__ . '/uploads/';
$pdfs = glob($uploadDir . '*.pdf');

if (empty($pdfs)) {
    echo "<div class='section error'>";
    echo "<h2>❌ No hay PDFs en la carpeta uploads/</h2>";
    echo "<p>Sube un PDF primero usando <a href='cargar.php' class='btn'>Cargar PDF</a></p>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

$pdfPath = $pdfs[0]; // Usar el primer PDF encontrado
echo "<div class='section success'>";
echo "<h2>✓ PDF Encontrado</h2>";
echo "<p><strong>Archivo:</strong> " . basename($pdfPath) . "</p>";
echo "<p><strong>Tamaño:</strong> " . number_format(filesize($pdfPath) / 1024, 2) . " KB</p>";
echo "<p><strong>Ruta:</strong> " . $pdfPath . "</p>";
echo "</div>";

// Paso 1: Extraer texto
echo "<div class='section'>";
echo "<h2>📄 Paso 1: Extracción de Texto</h2>";

$procesador = new ProcesadorPDFLibreria();

// Usar reflexión para acceder a métodos privados
$reflection = new ReflectionClass($procesador);
$metodoExtraer = $reflection->getMethod('extraerConComando');
$metodoExtraer->setAccessible(true);

$texto = $metodoExtraer->invoke($procesador, $pdfPath);

if (empty($texto)) {
    echo "<p class='warning'>⚠ Comando pdftotext no disponible, intentando método directo...</p>";
    
    $metodoDirecto = $reflection->getMethod('extraerDirectamente');
    $metodoDirecto->setAccessible(true);
    $texto = $metodoDirecto->invoke($procesador, $pdfPath);
}

if (empty($texto)) {
    echo "<p class='error'>❌ No se pudo extraer texto</p>";
} else {
    echo "<p class='success'>✓ Texto extraído: " . strlen($texto) . " caracteres</p>";
    
    // Mostrar primeras líneas
    $lineas = explode("\n", $texto);
    $primerasLineas = array_slice($lineas, 0, 20);
    
    echo "<h3>Primeras 20 líneas:</h3>";
    echo "<pre>";
    foreach ($primerasLineas as $i => $linea) {
        echo sprintf("%3d: %s\n", $i + 1, htmlspecialchars($linea));
    }
    echo "</pre>";
}
echo "</div>";

// Paso 2: Parsear datos
echo "<div class='section'>";
echo "<h2>🔬 Paso 2: Parseo de Datos</h2>";

$metodoParsear = $reflection->getMethod('parsearDatosAvanzado');
$metodoParsear->setAccessible(true);

$datos = $metodoParsear->invoke($procesador, $texto);

echo "<h3>Información del Evento:</h3>";
echo "<pre>";
echo "Número de Evento: " . ($datos['evento']['numero'] ?? 'NO ENCONTRADO') . "\n";
echo "Título: " . ($datos['evento']['titulo'] ?? 'NO ENCONTRADO') . "\n";
echo "Sesión: " . ($datos['evento']['sesion'] ?? 'NO ENCONTRADO') . "\n";
echo "Fecha/Hora: " . ($datos['evento']['fecha_hora'] ?? 'NO ENCONTRADO') . "\n";
echo "</pre>";

echo "<h3>Votos Extraídos:</h3>";
if (empty($datos['votos'])) {
    echo "<p class='error'>❌ No se encontraron votos</p>";
    echo "<p class='warning'>Intentando con parser alternativo...</p>";
    
    $metodoAlt = $reflection->getMethod('parsearDatosAlternativo');
    $metodoAlt->setAccessible(true);
    $datos = $metodoAlt->invoke($procesador, $texto);
    
    if (empty($datos['votos'])) {
        echo "<p class='error'>❌ Tampoco se encontraron votos con parser alternativo</p>";
    } else {
        echo "<p class='success'>✓ Encontrados con parser alternativo: " . count($datos['votos']) . " votos</p>";
    }
}

if (!empty($datos['votos'])) {
    echo "<p class='success'>✓ Total votos encontrados: " . count($datos['votos']) . "</p>";
    
    // Mostrar primeros 10 votos
    echo "<h4>Primeros 10 votos:</h4>";
    foreach (array_slice($datos['votos'], 0, 10) as $voto) {
        echo "<div class='voto'>";
        echo "<strong>#{$voto['numero']}</strong> - ";
        echo htmlspecialchars($voto['nombre']) . " | ";
        echo "<span class='warning'>" . htmlspecialchars(substr($voto['bloque'], 0, 40)) . "</span> | ";
        echo "<span class='success'>{$voto['voto']}</span>";
        echo "</div>";
    }
    
    if (count($datos['votos']) > 10) {
        echo "<p>... y " . (count($datos['votos']) - 10) . " votos más</p>";
    }
}

echo "</div>";

// Paso 3: Procesar realmente
echo "<div class='section'>";
echo "<h2>💾 Paso 3: Guardar en Base de Datos</h2>";

if (!empty($datos['votos'])) {
    $resultado = $procesador->procesarPDF($pdfPath);
    
    if ($resultado['success']) {
        echo "<p class='success'>✓ ¡Procesamiento exitoso!</p>";
        echo "<p>Evento ID: {$resultado['evento_id']}</p>";
        echo "<p>Total votos guardados: {$resultado['total_votos']}</p>";
        echo "<p><a href='index.php' class='btn'>Ver Dashboard</a></p>";
        echo "<p><a href='estadisticas.php' class='btn'>Ver Estadísticas</a></p>";
    } else {
        echo "<p class='error'>❌ Error: {$resultado['error']}</p>";
    }
} else {
    echo "<p class='error'>❌ No hay datos para guardar</p>";
    echo "<h3>Diagnóstico:</h3>";
    echo "<ul>";
    echo "<li>El PDF puede estar en formato imagen (escaneado)</li>";
    echo "<li>El formato del PDF puede ser diferente al esperado</li>";
    echo "<li>Puede haber problemas con la codificación de caracteres</li>";
    echo "</ul>";
    
    echo "<h3>Soluciones:</h3>";
    echo "<ol>";
    echo "<li>Verifica que el PDF tiene texto seleccionable (no es una imagen)</li>";
    echo "<li>Intenta con otro PDF</li>";
    echo "<li>Revisa el formato esperado en la documentación</li>";
    echo "</ol>";
}

echo "</div>";

// Botón para subir otro PDF
echo "<div class='section'>";
echo "<p><a href='cargar.php' class='btn'>Cargar Otro PDF</a></p>";
echo "<p><a href='verificar.php' class='btn'>Verificar Sistema</a></p>";
echo "</div>";

echo "</body></html>";
?>
