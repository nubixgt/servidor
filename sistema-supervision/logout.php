<?php
// logout.php
require_once 'config/config.php';

// Guardar nombre de usuario antes de destruir sesión (opcional)
$usuario = $_SESSION['usuario'] ?? 'Usuario';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir al login con parámetro de logout exitoso
header('Location: ' . SITE_URL . '/login.php?logout=success');
exit;