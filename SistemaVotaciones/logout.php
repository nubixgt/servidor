<?php
/**
 * logout.php - Cerrar sesión
 */

session_start();

// Guardar ID de usuario antes de destruir la sesión
$usuario_id = $_SESSION['usuario_id'] ?? null;
$session_id = session_id();

// Eliminar sesión activa de la base de datos
if ($usuario_id) {
    try {
        require_once 'config.php';
        $db = getDB();
        
        // Registrar logout en log
        $stmt = $db->prepare("INSERT INTO log_accesos (usuario_id, ip_address, user_agent, accion) VALUES (?, ?, ?, 'logout')");
        $stmt->execute([
            $usuario_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Eliminar sesión activa
        $stmt = $db->prepare("DELETE FROM sesiones_activas WHERE session_id = ?");
        $stmt->execute([$session_id]);
        
    } catch (Exception $e) {
        error_log('Error al cerrar sesión: ' . $e->getMessage());
    }
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir al login con mensaje
header('Location: login.php?logout=1');
exit;