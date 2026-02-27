<?php
/**
 * Script de diagnóstico y creación de carpeta uploads
 * Ejecutar para verificar/crear la carpeta de archivos
 */

require_once 'config.php';

$upload_dir = __DIR__ . '/uploads';
$bitacora_dir = __DIR__ . '/uploads/bitacora';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico de Uploads - MAGA</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0abde3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #1e3a5f;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
        }
        .icon {
            font-size: 24px;
        }
        .info {
            flex: 1;
        }
        .info strong {
            display: block;
            margin-bottom: 4px;
        }
        .info small {
            opacity: 0.8;
        }
        .btn {
            background: linear-gradient(135deg, #0abde3 0%, #48d1ff 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.4);
        }
        code {
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class='box'>
        <h1>🔧 Diagnóstico de Carpeta Uploads</h1>";

$all_ok = true;

// 1. Verificar/crear carpeta uploads
echo "<div class='check-item ";
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "success'><span class='icon'>✅</span><div class='info'><strong>Carpeta uploads creada</strong><small>$upload_dir</small></div>";
    } else {
        echo "error'><span class='icon'>❌</span><div class='info'><strong>No se pudo crear carpeta uploads</strong><small>Créala manualmente: $upload_dir</small></div>";
        $all_ok = false;
    }
} else {
    echo "success'><span class='icon'>✅</span><div class='info'><strong>Carpeta uploads existe</strong><small>$upload_dir</small></div>";
}
echo "</div>";

// 2. Verificar/crear carpeta bitacora
echo "<div class='check-item ";
if (!file_exists($bitacora_dir)) {
    if (mkdir($bitacora_dir, 0755, true)) {
        echo "success'><span class='icon'>✅</span><div class='info'><strong>Carpeta bitacora creada</strong><small>$bitacora_dir</small></div>";
    } else {
        echo "error'><span class='icon'>❌</span><div class='info'><strong>No se pudo crear carpeta bitacora</strong><small>Créala manualmente: $bitacora_dir</small></div>";
        $all_ok = false;
    }
} else {
    echo "success'><span class='icon'>✅</span><div class='info'><strong>Carpeta bitacora existe</strong><small>$bitacora_dir</small></div>";
}
echo "</div>";

// 3. Verificar permisos de escritura
echo "<div class='check-item ";
if (file_exists($bitacora_dir) && is_writable($bitacora_dir)) {
    echo "success'><span class='icon'>✅</span><div class='info'><strong>Permisos de escritura OK</strong><small>La carpeta es escribible</small></div>";
} else {
    echo "error'><span class='icon'>❌</span><div class='info'><strong>Sin permisos de escritura</strong><small>Ejecuta: <code>chmod 755 $bitacora_dir</code></small></div>";
    $all_ok = false;
}
echo "</div>";

// 4. Verificar configuración PHP
$max_upload = ini_get('upload_max_filesize');
$max_post = ini_get('post_max_size');

echo "<div class='check-item success'><span class='icon'>📊</span><div class='info'><strong>Configuración PHP</strong><small>upload_max_filesize: $max_upload | post_max_size: $max_post</small></div></div>";

// 5. Intentar crear archivo de prueba
echo "<div class='check-item ";
$test_file = $bitacora_dir . '/test_' . time() . '.txt';
if (file_exists($bitacora_dir)) {
    if (file_put_contents($test_file, 'test')) {
        unlink($test_file); // Eliminar archivo de prueba
        echo "success'><span class='icon'>✅</span><div class='info'><strong>Prueba de escritura exitosa</strong><small>Se puede crear archivos en la carpeta</small></div>";
    } else {
        echo "error'><span class='icon'>❌</span><div class='info'><strong>No se puede escribir en la carpeta</strong><small>Verifica permisos del servidor web</small></div>";
        $all_ok = false;
    }
} else {
    echo "warning'><span class='icon'>⚠️</span><div class='info'><strong>No se pudo probar escritura</strong><small>La carpeta no existe</small></div>";
    $all_ok = false;
}
echo "</div>";

// 6. Crear .htaccess de seguridad
$htaccess_file = $bitacora_dir . '/.htaccess';
if (file_exists($bitacora_dir) && !file_exists($htaccess_file)) {
    file_put_contents($htaccess_file, "Options -Indexes\n");
    echo "<div class='check-item success'><span class='icon'>🔒</span><div class='info'><strong>.htaccess creado</strong><small>Protección de directorio activada</small></div></div>";
}

// Resumen
echo "<hr style='margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;'>";

if ($all_ok) {
    echo "<div class='check-item success'>
        <span class='icon'>🎉</span>
        <div class='info'>
            <strong>¡Todo listo!</strong>
            <small>La carpeta de uploads está configurada correctamente</small>
        </div>
    </div>";
} else {
    echo "<div class='check-item error'>
        <span class='icon'>⚠️</span>
        <div class='info'>
            <strong>Hay problemas que resolver</strong>
            <small>Revisa los errores arriba y corrige los permisos</small>
        </div>
    </div>";
}

echo "
        <a href='listar_vales.php' class='btn'>Ir al Sistema</a>
        
        <p style='margin-top: 20px; color: #6b7280; font-size: 13px;'>
            ⚠️ Elimina este archivo después de usarlo.
        </p>
    </div>
</body>
</html>";
?>