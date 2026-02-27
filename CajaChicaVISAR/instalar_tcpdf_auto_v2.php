<?php
/**
 * INSTALADOR AUTOMÁTICO DE TCPDF - VERSIÓN COMPATIBLE
 * Corrige el error de ZipArchive::extractAll()
 * 
 * USO: Abrir este archivo en el navegador
 * Ejemplo: http://localhost/CajaChicaVISAR/instalar_tcpdf_auto_v2.php
 */

set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '256M');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Automático TCPDF v2</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .step {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 5px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px 5px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .center {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalador Automático TCPDF v2</h1>
        
        <?php
        $already_installed = false;
        $error_occurred = false;
        
        // Verificar si ya está instalado
        if (is_dir('tcpdf') && file_exists('tcpdf/tcpdf.php')) {
            $already_installed = true;
            echo '<div class="step success">';
            echo '<span class="icon">✅</span>';
            echo '<strong>TCPDF ya está instalado en este sistema</strong><br>';
            echo 'Ubicación: ' . realpath('tcpdf/tcpdf.php');
            echo '</div>';
            
            echo '<div class="center">';
            echo '<a href="test_tcpdf.php" class="btn">Verificar Instalación</a>';
            echo '<a href="index.php" class="btn btn-success">Ir al Sistema de Vales</a>';
            echo '</div>';
            
        } else {
            // Iniciar instalación
            echo '<div class="step info">';
            echo '<span class="icon">ℹ️</span>';
            echo '<strong>Iniciando instalación de TCPDF...</strong>';
            echo '</div>';
            
            // URL del repositorio
            $zip_url = "https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip";
            $zip_file = "tcpdf-temp.zip";
            
            // Paso 1: Verificar extensiones
            echo '<div class="step info">';
            echo '<span class="icon">🔍</span>';
            echo '<strong>Paso 1: Verificando extensiones PHP...</strong><br>';
            
            $curl_enabled = function_exists('curl_init');
            $zip_enabled = class_exists('ZipArchive');
            
            echo 'cURL: ' . ($curl_enabled ? '✅ Disponible' : '❌ No disponible') . '<br>';
            echo 'ZipArchive: ' . ($zip_enabled ? '✅ Disponible' : '❌ No disponible');
            echo '</div>';
            
            if (!$curl_enabled || !$zip_enabled) {
                echo '<div class="step error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>Error: Extensiones requeridas no disponibles</strong><br><br>';
                echo '<strong>Solución alternativa:</strong><br>';
                echo '1. Descarga manualmente desde: <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" target="_blank">GitHub</a><br>';
                echo '2. Extrae el archivo ZIP<br>';
                echo '3. Renombra la carpeta a "tcpdf"<br>';
                echo '4. Cópiala a: ' . getcwd();
                echo '</div>';
                $error_occurred = true;
            }
            
            if (!$error_occurred) {
                // Paso 2: Descargar
                echo '<div class="step info">';
                echo '<span class="icon">⬇️</span>';
                echo '<strong>Paso 2: Descargando TCPDF desde GitHub...</strong><br>';
                echo 'Esto puede tomar varios minutos...<br>';
                flush();
                ob_flush();
                
                $ch = curl_init($zip_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);
                
                $zip_content = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                if ($http_code != 200 || !$zip_content) {
                    echo '<strong style="color:red;">Error al descargar</strong><br>';
                    echo 'Código HTTP: ' . $http_code . '<br>';
                    if ($curl_error) {
                        echo 'Error cURL: ' . $curl_error . '<br>';
                    }
                    echo '</div>';
                    
                    echo '<div class="step error">';
                    echo '<span class="icon">❌</span>';
                    echo '<strong>No se pudo descargar automáticamente</strong><br><br>';
                    echo '<strong>Descarga manual:</strong><br>';
                    echo '<a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" class="btn" target="_blank">Descargar TCPDF</a>';
                    echo '</div>';
                    $error_occurred = true;
                    
                } else {
                    echo '✅ Descarga completada (' . number_format(strlen($zip_content) / 1024 / 1024, 2) . ' MB)';
                    echo '</div>';
                    
                    // Paso 3: Guardar archivo
                    echo '<div class="step info">';
                    echo '<span class="icon">💾</span>';
                    echo '<strong>Paso 3: Guardando archivo temporal...</strong><br>';
                    
                    if (file_put_contents($zip_file, $zip_content)) {
                        echo '✅ Archivo guardado';
                        echo '</div>';
                    } else {
                        echo '❌ Error al guardar archivo';
                        echo '</div>';
                        $error_occurred = true;
                    }
                }
            }
            
            if (!$error_occurred) {
                // Paso 4: Extraer (CORREGIDO)
                echo '<div class="step info">';
                echo '<span class="icon">📦</span>';
                echo '<strong>Paso 4: Extrayendo archivos...</strong><br>';
                flush();
                ob_flush();
                
                $zip = new ZipArchive;
                if ($zip->open($zip_file) === TRUE) {
                    // SOLUCIÓN AL ERROR: Usar bucle en lugar de extractAll()
                    $extract_path = './';
                    $extracted_count = 0;
                    
                    for($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        
                        // Extraer archivo por archivo
                        if ($zip->extractTo($extract_path, array($filename))) {
                            $extracted_count++;
                        }
                        
                        // Mostrar progreso cada 100 archivos
                        if ($extracted_count % 100 == 0) {
                            echo $extracted_count . ' archivos extraídos...<br>';
                            flush();
                            ob_flush();
                        }
                    }
                    
                    $zip->close();
                    echo '✅ ' . $extracted_count . ' archivos extraídos correctamente';
                    echo '</div>';
                    
                    // Paso 5: Renombrar
                    echo '<div class="step info">';
                    echo '<span class="icon">✏️</span>';
                    echo '<strong>Paso 5: Organizando archivos...</strong><br>';
                    
                    if (is_dir('TCPDF-main')) {
                        if (rename('TCPDF-main', 'tcpdf')) {
                            echo '✅ Carpeta renombrada a "tcpdf"';
                        } else {
                            echo '❌ Error al renombrar carpeta<br>';
                            echo 'Intenta renombrar manualmente TCPDF-main a tcpdf';
                            $error_occurred = true;
                        }
                    } else {
                        echo '❌ No se encontró la carpeta extraída';
                        $error_occurred = true;
                    }
                    echo '</div>';
                    
                    // Paso 6: Limpiar
                    echo '<div class="step info">';
                    echo '<span class="icon">🧹</span>';
                    echo '<strong>Paso 6: Limpiando archivos temporales...</strong><br>';
                    
                    if (file_exists($zip_file)) {
                        unlink($zip_file);
                        echo '✅ Archivos temporales eliminados';
                    }
                    echo '</div>';
                    
                } else {
                    echo '❌ Error al abrir el archivo ZIP';
                    echo '</div>';
                    $error_occurred = true;
                }
            }
            
            // Verificar instalación final
            if (!$error_occurred && file_exists('tcpdf/tcpdf.php')) {
                echo '<div class="step success">';
                echo '<span class="icon">🎉</span>';
                echo '<strong>¡INSTALACIÓN COMPLETADA EXITOSAMENTE!</strong><br>';
                echo 'TCPDF se ha instalado correctamente en: ' . realpath('tcpdf') . '<br>';
                echo 'Tamaño total: ' . number_format(folderSize('tcpdf') / 1024 / 1024, 2) . ' MB';
                echo '</div>';
                
                echo '<div class="center">';
                echo '<a href="test_tcpdf.php" class="btn">Verificar Instalación</a>';
                echo '<a href="index.php" class="btn btn-success">Ir al Sistema de Vales</a>';
                echo '</div>';
                
            } elseif (!$error_occurred) {
                echo '<div class="step error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>Error: No se pudo completar la instalación</strong><br>';
                echo 'El archivo tcpdf/tcpdf.php no se encontró después de la instalación.';
                echo '</div>';
            }
        }
        
        // Función auxiliar para calcular tamaño de carpeta
        function folderSize($dir) {
            $size = 0;
            if (is_dir($dir)) {
                foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file){
                    $size += $file->getSize();
                }
            }
            return $size;
        }
        
        // Instrucciones manuales al final
        if (!$already_installed) {
            echo '<div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd;">';
            echo '<h3>📚 Instalación Manual (Si el automático falla)</h3>';
            echo '<ol style="line-height: 2;">';
            echo '<li>Descarga: <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" target="_blank">TCPDF desde GitHub</a></li>';
            echo '<li>Extrae el archivo ZIP con tu programa favorito (WinRAR, 7-Zip, etc.)</li>';
            echo '<li>Renombra la carpeta extraída a <code>tcpdf</code> (minúsculas)</li>';
            echo '<li>Copia la carpeta a: <code>' . getcwd() . '</code></li>';
            echo '<li>Verifica con: <a href="test_tcpdf.php">test_tcpdf.php</a></li>';
            echo '</ol>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
            <p>Sistema de Vales de Caja Chica - VISAR<br>
            Instalador Automático v2.0 - Versión Mejorada</p>
        </div>
    </div>
</body>
</html>