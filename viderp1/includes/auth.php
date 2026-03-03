<?php
/**
 * Sistema de Autenticación - VIDER
 * MAGA Guatemala
 * 
 * Incluir este archivo al inicio de cada página protegida
 * Uso: require_once 'includes/auth.php';
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 * Redirige a login si no lo está
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?error=Debe iniciar sesión para acceder');
        exit;
    }
    
    // Verificar tiempo de sesión (8 horas máximo)
    $maxSessionTime = 8 * 60 * 60;
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $maxSessionTime)) {
        logout();
        header('Location: login.php?error=Su sesión ha expirado. Por favor inicie sesión nuevamente.');
        exit;
    }
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'rol' => $_SESSION['rol'] ?? 'visor'
    ];
}

/**
 * Obtener el nombre del usuario actual
 */
function getUserName() {
    return $_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario';
}

/**
 * Obtener el rol del usuario actual
 */
function getUserRole() {
    return $_SESSION['rol'] ?? 'visor';
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return getUserRole() === $role;
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    return getUserRole() === 'admin';
}

/**
 * Verificar si el usuario es técnico
 */
function isTecnico() {
    return getUserRole() === 'tecnico';
}

/**
 * Verificar si el usuario puede cargar/importar datos
 * Solo los técnicos pueden cargar información
 */
function canImport() {
    return isTecnico();
}

/**
 * Verificar si el usuario puede editar datos
 * Solo los técnicos pueden editar
 */
function canEdit() {
    return isTecnico();
}

/**
 * Verificar si el usuario puede gestionar usuarios
 * Solo los administradores pueden gestionar usuarios
 */
function canManageUsers() {
    return isAdmin();
}

/**
 * Cerrar sesión
 */
function logout() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Requerir rol específico para acceder
 */
function requireRole($requiredRole) {
    requireLogin();
    
    if (!hasRole($requiredRole)) {
        header('Location: index.php?error=No tiene permisos para acceder a esta sección');
        exit;
    }
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtener campo hidden con token CSRF
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}