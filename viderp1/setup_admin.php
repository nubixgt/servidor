<?php
/**
 * Script de configuración inicial - VIDER
 * Ejecutar UNA VEZ para configurar la contraseña del administrador
 * ELIMINAR ESTE ARCHIVO DESPUÉS DE USAR
 */

require_once 'includes/config.php';

echo "<h2>Configuración de Usuario Admin - VIDER</h2>";

try {
    $db = Database::getInstance();
    
    // Generar hash correcto para admin123
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Actualizar en la base de datos
    $sql = "UPDATE usuarios SET password = :password WHERE username = 'admin'";
    $result = $db->query($sql, [':password' => $hash]);
    
    echo "<p style='color: green; font-size: 18px;'>✅ Contraseña actualizada correctamente</p>";
    echo "<p><strong>Usuario:</strong> admin</p>";
    echo "<p><strong>Contraseña:</strong> admin123</p>";
    echo "<br>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (setup_admin.php) después de usarlo por seguridad.</p>";
    echo "<br>";
    echo "<a href='login.php' style='padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px;'>Ir al Login</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>