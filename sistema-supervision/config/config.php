<?php
/**
 * config/config.php 
 * Configuración General del Sistema
 * Sistema de Supervisión v6.0.4 - Sistema de Niveles de Acceso
 */

// ✨ CONFIGURACIÓN DE TIMEOUT
define('SESSION_TIMEOUT', 1800); // 🧪 PRUEBA: 10 seg | PRODUCCIÓN: 1800 (30 min)

// ✨ PASO 1: Configurar sesión ANTES de iniciarla
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params(SESSION_TIMEOUT);

// ✨ PASO 2: Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Guatemala');

// ✨ PASO 3: Definir constantes (antes de usarlas)
define('SITE_NAME', 'Sistema de Supervisión');
define('SITE_URL', 'https://m.nubix.gt/sistema-supervision/');
define('BASE_PATH', '/home/visionwe/m.nubix.gt/sistema-supervision/');

// ✨ PASO 4: Verificar expiración de sesión (solo si está logueado)
if (isset($_SESSION['user_id'])) {
    // Solo verificar timeout si el usuario está logueado
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        // Si han pasado más de X segundos, destruir sesión
        session_unset();
        session_destroy();
        header('Location: ' . SITE_URL . '/login.php?sesion=expirada');
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // Actualizar timestamp
}

// Roles de usuario
define('ROLE_ADMIN', 'administrador');
define('ROLE_TECNICO', 'tecnico');

// ✨ NUEVO: Niveles de acceso para técnicos
define('NIVEL_BASICO', 'basico');      // Solo supervisiones
define('NIVEL_COMPLETO', 'completo');  // Supervisiones + Inventario

// Estados de usuario
define('STATUS_ACTIVE', 'activo');
define('STATUS_PENDING', 'pendiente');
define('STATUS_SUSPENDED', 'suspendido');
define('STATUS_INACTIVE', 'inactivo');

// Incluir base de datos
require_once __DIR__ . '/database.php';

/**
 * Función para verificar si el usuario está logueado
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['usuario']);
}

/**
 * Función para verificar el rol del usuario
 */
function checkRole($requiredRole)
{
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['rol'] === $requiredRole;
}

/**
 * ✨ NUEVO: Función para obtener el nivel de acceso del usuario
 */
function getNivelAcceso()
{
    if (!isLoggedIn()) {
        return null;
    }
    return $_SESSION['nivel_acceso'] ?? null;
}

/**
 * ✨ NUEVO: Verificar si el usuario tiene acceso a un módulo específico
 */
function tieneAccesoModulo($modulo)
{
    if (!isLoggedIn()) {
        return false;
    }

    $rol = $_SESSION['rol'] ?? '';
    $nivelAcceso = getNivelAcceso();

    // Administrador tiene acceso a todo
    if ($rol === ROLE_ADMIN) {
        return true;
    }

    // Técnicos
    if ($rol === ROLE_TECNICO) {
        switch ($modulo) {
            case 'dashboard':
            case 'supervisiones':
            case 'nueva-supervision':
            case 'reportes':
                // Ambos niveles pueden acceder
                return true;

            case 'inventario':
                // Solo técnicos completos
                return $nivelAcceso === NIVEL_COMPLETO;

            case 'empleados':
            case 'contratistas':
            case 'proyectos':
            case 'usuarios':
                // Solo admin
                return false;

            default:
                return false;
        }
    }

    return false;
}

/**
 * ✨ NUEVO: Verificar acceso al módulo y redirigir si no tiene permiso
 */
function verificarAccesoModulo($modulo)
{
    if (!tieneAccesoModulo($modulo)) {
        $_SESSION['mensaje_error'] = 'No tienes permiso para acceder a este módulo.';

        // Redirigir según rol
        if (checkRole(ROLE_ADMIN)) {
            header('Location: ' . SITE_URL . '/modules/admin/dashboard.php');
        } else {
            header('Location: ' . SITE_URL . '/modules/tecnico/dashboard.php');
        }
        exit;
    }
}

/**
 * ✨ NUEVO: Obtener badge HTML del nivel de acceso
 */
function getBadgeNivel()
{
    // ✅ Verificar que existe la sesión
    if (!isLoggedIn()) {
        return '';
    }

    $rol = $_SESSION['rol'] ?? '';
    $nivelAcceso = getNivelAcceso();

    if ($rol === ROLE_ADMIN) {
        return '<span class="role-badge role-admin">Administrador</span>';
    }

    if ($rol === ROLE_TECNICO) {
        if ($nivelAcceso === NIVEL_COMPLETO) {
            return '<span class="role-badge role-tecnico-completo">Técnico Completo</span>';
        } else {
            return '<span class="role-badge role-tecnico-basico">Técnico Básico</span>';
        }
    }

    return '';
}

/**
 * Función para redirigir según el rol
 */
function redirectByRole()
{
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }

    if ($_SESSION['rol'] === ROLE_ADMIN) {
        header('Location: ' . SITE_URL . '/modules/admin/dashboard.php');
    } elseif ($_SESSION['rol'] === ROLE_TECNICO) {
        header('Location: ' . SITE_URL . '/modules/tecnico/dashboard.php');
    }
    exit;
}

/**
 * Función para proteger páginas
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Función para proteger páginas de admin
 */
function requireAdmin()
{
    requireLogin();
    if (!checkRole(ROLE_ADMIN)) {
        header('Location: ' . SITE_URL . '/modules/tecnico/dashboard.php');
        exit;
    }
}

/**
 * Función para proteger páginas de técnico
 */
function requireTecnico()
{
    requireLogin();
    if (!checkRole(ROLE_TECNICO)) {
        header('Location: ' . SITE_URL . '/modules/admin/dashboard.php');
        exit;
    }
}

/**
 * Función para limpiar datos de entrada
 */
function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>