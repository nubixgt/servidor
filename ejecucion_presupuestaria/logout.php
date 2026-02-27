<?php
/**
 * Logout - Sistema de Ejecución Presupuestaria
 * Cierra la sesión del usuario
 */

require_once 'config/database.php';

// Registrar en bitácora si hay sesión activa
if (isset($_SESSION['usuario_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO bitacora (usuario_id, tabla_afectada, registro_id, accion, datos_nuevos, ip_address, user_agent) VALUES (?, 'usuarios', ?, 'DELETE', ?, ?, ?)");
        $stmt->execute([
            $_SESSION['usuario_id'],
            $_SESSION['usuario_id'],
            json_encode(['tipo' => 'LOGOUT', 'accion' => 'Cierre de sesión']),
            $_SERVER['REMOTE_ADDR'] ?? 'localhost',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Continuar con el logout aunque falle el registro
    }
}

// Destruir sesión
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Redirigir al login
header('Location: login.php');
exit;
