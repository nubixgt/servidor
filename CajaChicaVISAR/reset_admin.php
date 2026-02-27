<?php
/**
 * Script para resetear usuario admin
 * Ejecutar UNA VEZ y luego ELIMINAR este archivo
 */

require_once 'config.php';

try {
    $db = getDB();
    
    // Contraseña que queremos usar
    $password = 'admin123';
    
    // Generar hash bcrypt
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "<h2>🔧 Reset de Usuario Admin</h2>";
    echo "<p><strong>Hash generado:</strong> " . htmlspecialchars($hash) . "</p>";
    
    // Eliminar todos los usuarios
    $db->exec("DELETE FROM usuarios");
    echo "<p>✅ Usuarios anteriores eliminados</p>";
    
    // Resetear auto_increment
    $db->exec("ALTER TABLE usuarios AUTO_INCREMENT = 1");
    
    // Insertar nuevo admin
    $stmt = $db->prepare("INSERT INTO usuarios (usuario, nombre_completo, password, rol, activo, fecha_creacion) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute(['admin', 'Administrador del Sistema', $hash, 'ADMIN', 1]);
    
    echo "<p>✅ Usuario admin creado exitosamente</p>";
    echo "<hr>";
    echo "<h3>Credenciales:</h3>";
    echo "<ul>";
    echo "<li><strong>Usuario:</strong> admin</li>";
    echo "<li><strong>Contraseña:</strong> admin123</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (reset_admin.php) después de usarlo.</p>";
    echo "<p><a href='login.php'>👉 Ir al Login</a></p>";
    
    // Verificar que funciona
    echo "<hr><h3>Verificación:</h3>";
    $stmt = $db->query("SELECT * FROM usuarios WHERE usuario = 'admin'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>Usuario encontrado en BD: " . htmlspecialchars($user['usuario']) . "</p>";
        
        // Probar verificación de contraseña
        if (password_verify('admin123', $user['password'])) {
            echo "<p style='color: green;'>✅ Verificación de contraseña: CORRECTA</p>";
        } else {
            echo "<p style='color: red;'>❌ Verificación de contraseña: FALLÓ</p>";
        }
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>