<?php
/**
 * SCRIPT DE DEBUG PARA LOGIN
 * Este archivo te ayudará a identificar el problema exacto
 * 
 * Ejecutar: http://localhost/congreso/debug_login.php
 */

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Login</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: #f8f9fa; padding: 2rem; font-family: 'Courier New', monospace; }
        .test { padding: 1rem; margin: 1rem 0; border-radius: 8px; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; }
        .error { background: #fee2e2; border-left: 4px solid #ef4444; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
        .info { background: #dbeafe; border-left: 4px solid #3b82f6; }
        .code { background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='mb-4'>🔍 Debug del Sistema de Login</h1>
    <p class='text-muted'>Este script verifica cada componente del sistema de autenticación</p>
    <hr>";

// TEST 1: Verificar archivo config.php
echo "<div class='test info'>";
echo "<h3>📁 TEST 1: Archivo config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ El archivo config.php existe<br>";
    require_once 'config.php';
    echo "✅ El archivo se cargó correctamente";
} else {
    echo "❌ ERROR: No se encuentra config.php";
}
echo "</div>";

// TEST 2: Conexión a base de datos
echo "<div class='test info'>";
echo "<h3>🗄️ TEST 2: Conexión a Base de Datos</h3>";
try {
    $db = getDB();
    echo "✅ Conexión exitosa a la base de datos<br>";
    echo "Base de datos: <strong>" . DB_NAME . "</strong>";
} catch (Exception $e) {
    echo "❌ ERROR de conexión: " . htmlspecialchars($e->getMessage());
    echo "<div class='code mt-2'>";
    echo "Verifica en config.php:<br>";
    echo "DB_HOST = '" . (defined('DB_HOST') ? DB_HOST : 'NO DEFINIDO') . "'<br>";
    echo "DB_NAME = '" . (defined('DB_NAME') ? DB_NAME : 'NO DEFINIDO') . "'<br>";
    echo "DB_USER = '" . (defined('DB_USER') ? DB_USER : 'NO DEFINIDO') . "'";
    echo "</div>";
}
echo "</div>";

// TEST 3: Verificar tabla usuarios
echo "<div class='test info'>";
echo "<h3>👥 TEST 3: Tabla de Usuarios</h3>";
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "✅ La tabla 'usuarios' existe<br>";
        
        // Contar usuarios
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
        $total = $stmt->fetch()['total'];
        echo "📊 Total de usuarios: <strong>$total</strong><br>";
        
        // Ver usuarios
        $stmt = $db->query("SELECT id, username, nombre_completo, tipo_usuario, activo FROM usuarios");
        $usuarios = $stmt->fetchAll();
        
        if (count($usuarios) > 0) {
            echo "<table class='table table-sm table-bordered mt-2'>
                  <tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Tipo</th><th>Activo</th></tr>";
            foreach ($usuarios as $u) {
                echo "<tr>
                    <td>{$u['id']}</td>
                    <td><strong>{$u['username']}</strong></td>
                    <td>{$u['nombre_completo']}</td>
                    <td><span class='badge bg-" . ($u['tipo_usuario'] == 'admin' ? 'danger' : 'info') . "'>{$u['tipo_usuario']}</span></td>
                    <td>" . ($u['activo'] ? '✅ Sí' : '❌ No') . "</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error mt-2'>❌ No hay usuarios en la tabla. Debes ejecutar usuarios_login.sql</div>";
        }
        
    } else {
        echo "<div class='error'>❌ La tabla 'usuarios' NO existe<br>";
        echo "👉 Debes ejecutar el archivo <strong>usuarios_login.sql</strong> en phpMyAdmin</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// TEST 4: Verificar contraseñas
echo "<div class='test info'>";
echo "<h3>🔐 TEST 4: Verificar Contraseñas Hash</h3>";
try {
    $db = getDB();
    $stmt = $db->query("SELECT username, password FROM usuarios LIMIT 3");
    $usuarios = $stmt->fetchAll();
    
    echo "<table class='table table-sm table-bordered'>
          <tr><th>Usuario</th><th>Hash (primeros 20 caracteres)</th><th>Estado</th></tr>";
    
    foreach ($usuarios as $u) {
        $hashPreview = substr($u['password'], 0, 20) . '...';
        $isValidHash = substr($u['password'], 0, 4) === '$2y$';
        
        echo "<tr>
            <td><strong>{$u['username']}</strong></td>
            <td><code>{$hashPreview}</code></td>
            <td>" . ($isValidHash ? "✅ Hash válido" : "❌ Hash inválido") . "</td>
        </tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// TEST 5: Probar login manual
echo "<div class='test warning'>";
echo "<h3>🧪 TEST 5: Probar Login Manual</h3>";
echo "<form method='POST' class='mt-3'>
    <div class='row'>
        <div class='col-md-4'>
            <input type='text' name='test_user' class='form-control' placeholder='Usuario' value='admin'>
        </div>
        <div class='col-md-4'>
            <input type='text' name='test_pass' class='form-control' placeholder='Contraseña' value='admin123'>
        </div>
        <div class='col-md-4'>
            <button type='submit' name='test_login' class='btn btn-primary w-100'>🧪 Probar Login</button>
        </div>
    </div>
</form>";

if (isset($_POST['test_login'])) {
    $testUser = $_POST['test_user'] ?? '';
    $testPass = $_POST['test_pass'] ?? '';
    
    echo "<div class='mt-3'>";
    echo "<strong>Probando con:</strong><br>";
    echo "Usuario: <code>$testUser</code><br>";
    echo "Contraseña: <code>$testPass</code><br><br>";
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$testUser]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            echo "✅ Usuario encontrado en la base de datos<br>";
            echo "📋 Datos del usuario:<br>";
            echo "- ID: {$usuario['id']}<br>";
            echo "- Nombre: {$usuario['nombre_completo']}<br>";
            echo "- Tipo: {$usuario['tipo_usuario']}<br>";
            echo "- Activo: " . ($usuario['activo'] ? 'Sí' : 'No') . "<br><br>";
            
            if ($usuario['activo']) {
                echo "✅ Usuario está activo<br><br>";
                
                // Verificar contraseña
                echo "🔐 Verificando contraseña...<br>";
                if (password_verify($testPass, $usuario['password'])) {
                    echo "<div class='success mt-2 p-3'>";
                    echo "<strong>✅ ¡LOGIN EXITOSO!</strong><br>";
                    echo "La contraseña es correcta. El sistema de login debería funcionar.";
                    echo "</div>";
                } else {
                    echo "<div class='error mt-2 p-3'>";
                    echo "<strong>❌ Contraseña incorrecta</strong><br>";
                    echo "La contraseña que ingresaste no coincide con el hash en la base de datos.<br><br>";
                    echo "💡 <strong>Solución:</strong><br>";
                    echo "1. Usa el archivo <code>generar_password.php</code> para crear un nuevo hash<br>";
                    echo "2. O ejecuta este SQL en phpMyAdmin:<br>";
                    echo "<div class='code mt-2'>";
                    echo "UPDATE usuarios SET password = '" . password_hash($testPass, PASSWORD_DEFAULT) . "' WHERE username = '$testUser';";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div class='error mt-2'>❌ El usuario está INACTIVO. Actívalo con:<br>";
                echo "<div class='code mt-2'>UPDATE usuarios SET activo = 1 WHERE username = '$testUser';</div>";
                echo "</div>";
            }
            
        } else {
            echo "<div class='error mt-2'>";
            echo "❌ Usuario NO encontrado en la base de datos<br><br>";
            echo "💡 <strong>Solución:</strong> Ejecuta el archivo <strong>usuarios_login.sql</strong> en phpMyAdmin";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
}
echo "</div>";

// TEST 6: Verificar otras tablas
echo "<div class='test info'>";
echo "<h3>📊 TEST 6: Otras Tablas del Sistema</h3>";
$tablasRequeridas = ['log_accesos', 'sesiones_activas'];
foreach ($tablasRequeridas as $tabla) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$tabla' existe<br>";
        } else {
            echo "❌ Tabla '$tabla' NO existe<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error verificando '$tabla': " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}
echo "</div>";

// TEST 7: Verificar funciones de auth.php
echo "<div class='test info'>";
echo "<h3>🔧 TEST 7: Funciones del Sistema</h3>";
if (function_exists('password_verify')) {
    echo "✅ password_verify() disponible<br>";
} else {
    echo "❌ password_verify() NO disponible<br>";
}

if (function_exists('password_hash')) {
    echo "✅ password_hash() disponible<br>";
} else {
    echo "❌ password_hash() NO disponible<br>";
}

if (function_exists('sanitizar')) {
    echo "✅ sanitizar() cargada desde config.php<br>";
} else {
    echo "❌ sanitizar() NO disponible<br>";
}
echo "</div>";

// Resumen Final
echo "<div class='test " . (isset($_POST['test_login']) && isset($usuario) && password_verify($testPass, $usuario['password']) ? 'success' : 'warning') . "'>";
echo "<h3>📝 RESUMEN</h3>";
echo "<strong>Estado del Sistema de Login:</strong><br><br>";

$checks = [
    'config.php existe' => file_exists('config.php'),
    'Conexión a BD funciona' => isset($db),
    'Tabla usuarios existe' => isset($stmt),
    'Hay usuarios registrados' => isset($total) && $total > 0
];

$todoBien = true;
foreach ($checks as $check => $status) {
    echo ($status ? '✅' : '❌') . " $check<br>";
    if (!$status) $todoBien = false;
}

echo "<br>";
if ($todoBien) {
    echo "✅ <strong>Todo parece estar bien configurado.</strong><br>";
    echo "Si el login no funciona, prueba con el TEST 5 arriba.";
} else {
    echo "❌ <strong>Hay problemas de configuración.</strong><br>";
    echo "Revisa los errores arriba y corrígelos.";
}
echo "</div>";

echo "<div class='text-center mt-4'>
    <a href='login.php' class='btn btn-primary'>Ir al Login</a>
    <a href='index.php' class='btn btn-secondary'>Ir al Dashboard</a>
</div>";

echo "</div></body></html>";
?>