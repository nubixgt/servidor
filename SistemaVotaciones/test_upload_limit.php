<?php
/**
 * test_upload_limit.php
 * Script para verificar el límite real de archivos que puedes cargar
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Límite de Carga</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2563eb; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .ok { background: #d1fae5; color: #065f46; }
        .warning { background: #fef3c7; color: #92400e; }
        .error { background: #fee2e2; color: #991b1b; }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .alert-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #991b1b;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #065f46;
        }
        .code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            font-weight: 500;
        }
        .btn:hover { background: #1d4ed8; }
        .calculation {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Límite de Carga de Archivos</h1>
        
        <?php
        // Obtener valores actuales
        $maxFileUploads = ini_get('max_file_uploads');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        
        // Función para convertir a bytes
        function return_bytes($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = (int)$val;
            switch($last) {
                case 'g': $val *= 1024;
                case 'm': $val *= 1024;
                case 'k': $val *= 1024;
            }
            return $val;
        }
        
        // Convertir a bytes
        $uploadMaxBytes = return_bytes($uploadMaxFilesize);
        $postMaxBytes = return_bytes($postMaxSize);
        
        // Calcular capacidad real
        $maxFilesTheoretical = floor($postMaxBytes / $uploadMaxBytes);
        $maxFilesReal = min($maxFileUploads, $maxFilesTheoretical);
        
        // Determinar si puede cargar 30 archivos
        $puede30 = ($maxFilesReal >= 30);
        ?>
        
        <h2>📊 Configuración Actual</h2>
        <table>
            <tr>
                <th>Parámetro</th>
                <th>Valor Actual</th>
                <th>Recomendado para 30+ archivos</th>
                <th>Estado</th>
            </tr>
            <tr>
                <td><strong>max_file_uploads</strong></td>
                <td><?php echo $maxFileUploads; ?></td>
                <td>100</td>
                <td>
                    <?php if ($maxFileUploads >= 30): ?>
                        <span class="status ok">✅ OK</span>
                    <?php else: ?>
                        <span class="status error">❌ BAJO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>upload_max_filesize</strong></td>
                <td><?php echo $uploadMaxFilesize; ?></td>
                <td>50M</td>
                <td>
                    <?php if (return_bytes($uploadMaxFilesize) >= return_bytes('20M')): ?>
                        <span class="status ok">✅ OK</span>
                    <?php else: ?>
                        <span class="status warning">⚠️ MEJORAR</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>post_max_size</strong></td>
                <td><?php echo $postMaxSize; ?></td>
                <td>500M</td>
                <td>
                    <?php if (return_bytes($postMaxSize) >= return_bytes('300M')): ?>
                        <span class="status ok">✅ OK</span>
                    <?php else: ?>
                        <span class="status warning">⚠️ MEJORAR</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>max_execution_time</strong></td>
                <td><?php echo $maxExecutionTime == 0 ? 'Sin límite ✅' : $maxExecutionTime . ' seg'; ?></td>
                <td>0 (sin límite)</td>
                <td>
                    <?php if ($maxExecutionTime == 0 || $maxExecutionTime >= 300): ?>
                        <span class="status ok">✅ OK</span>
                    <?php else: ?>
                        <span class="status error">❌ BAJO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>memory_limit</strong></td>
                <td><?php echo $memoryLimit; ?></td>
                <td>512M</td>
                <td>
                    <?php if (return_bytes($memoryLimit) >= return_bytes('256M')): ?>
                        <span class="status ok">✅ OK</span>
                    <?php else: ?>
                        <span class="status warning">⚠️ MEJORAR</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <h2>🧮 Análisis de Capacidad</h2>
        <div class="calculation">
            <p><strong>Cálculos:</strong></p>
            <ul>
                <li>Tamaño máximo por archivo: <strong><?php echo $uploadMaxFilesize; ?></strong></li>
                <li>Tamaño máximo total (POST): <strong><?php echo $postMaxSize; ?></strong></li>
                <li>Límite de archivos configurado: <strong><?php echo $maxFileUploads; ?></strong></li>
                <li>Archivos teóricos por tamaño: <strong><?php echo $maxFilesTheoretical; ?></strong></li>
                <li><strong>Capacidad real: <?php echo $maxFilesReal; ?> archivos simultáneos</strong></li>
            </ul>
        </div>
        
        <?php if ($puede30): ?>
            <div class="success-box">
                <h3>✅ ¡Configuración Correcta!</h3>
                <p>Tu servidor está configurado para cargar <strong>30 o más archivos</strong> simultáneamente.</p>
                <p><strong>Puedes cargar tus 30 PDFs sin problemas.</strong></p>
            </div>
        <?php else: ?>
            <div class="alert-box">
                <h3>❌ Configuración Insuficiente</h3>
                <p>Tu servidor solo puede cargar <strong><?php echo $maxFilesReal; ?> archivos</strong> a la vez.</p>
                <p><strong>No puedes cargar 30 archivos simultáneamente.</strong></p>
            </div>
            
            <h2>🔧 Soluciones</h2>
            
            <h3>Opción 1: Editar php.ini (RECOMENDADO)</h3>
            <div class="info-box">
                <p><strong>Ubicación de tu php.ini:</strong></p>
                <div class="code"><?php echo php_ini_loaded_file(); ?></div>
                
                <p><strong>Pasos:</strong></p>
                <ol>
                    <li>Abre el archivo php.ini con Notepad++ o Bloc de notas (como Administrador)</li>
                    <li>Busca y modifica estas líneas:</li>
                </ol>
                
                <div class="code">max_file_uploads = 100
upload_max_filesize = 50M
post_max_size = 500M
max_execution_time = 0
memory_limit = 512M</div>
                
                <ol start="3">
                    <li>Guarda el archivo</li>
                    <li>Reinicia Apache desde XAMPP</li>
                    <li>Recarga esta página para verificar</li>
                </ol>
            </div>
            
            <h3>Opción 2: Usar Carga Avanzada (SIN EDITAR php.ini)</h3>
            <div class="info-box">
                <p>El archivo <code>carga_avanzada.php</code> procesa archivos <strong>uno por uno</strong> vía AJAX.</p>
                <p><strong>Ventajas:</strong></p>
                <ul>
                    <li>✅ Sin límite de archivos (puedes cargar 30, 50, 100+)</li>
                    <li>✅ Muestra progreso en tiempo real</li>
                    <li>✅ No requiere editar php.ini</li>
                    <li>✅ Maneja errores individualmente</li>
                </ul>
                <a href="carga_avanzada.php" class="btn">Ir a Carga Avanzada</a>
            </div>
            
            <h3>Opción 3: Cargar en Lotes</h3>
            <div class="info-box">
                <p>Divide tus 30 archivos en grupos de <?php echo $maxFilesReal; ?>:</p>
                <ul>
                    <li>Lote 1: <?php echo $maxFilesReal; ?> archivos</li>
                    <li>Lote 2: <?php echo min($maxFilesReal, 30 - $maxFilesReal); ?> archivos</li>
                    <?php if (30 > 2 * $maxFilesReal): ?>
                        <li>Lote 3: <?php echo 30 - (2 * $maxFilesReal); ?> archivos</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h2>ℹ️ Información del Sistema</h2>
        <table>
            <tr>
                <th>Parámetro</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Versión de PHP</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Sistema Operativo</td>
                <td><?php echo PHP_OS; ?></td>
            </tr>
            <tr>
                <td>Servidor Web</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></td>
            </tr>
            <tr>
                <td>Archivo php.ini</td>
                <td style="font-size: 11px;"><?php echo php_ini_loaded_file(); ?></td>
            </tr>
            <tr>
                <td>Hora de verificación</td>
                <td><?php echo date('Y-m-d H:i:s'); ?></td>
            </tr>
        </table>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="verificar.php" class="btn">Ver Diagnóstico Completo</a>
            <a href="javascript:location.reload()" class="btn">🔄 Recargar Test</a>
        </div>
    </div>
</body>
</html>