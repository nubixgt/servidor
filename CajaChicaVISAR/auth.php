<?php
/**
 * Sistema de Autenticación
 * MAGA - Sistema de Vales de Caja Chica
 */

require_once 'config.php';

/**
 * Verificar credenciales del usuario
 */
function verificarLogin($usuario, $password) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, usuario, nombre_completo, password, rol, activo 
                              FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if ($user && $user['activo'] == 1 && password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'usuario' => $user['usuario'],
                    'nombre_completo' => $user['nombre_completo'],
                    'rol' => $user['rol']
                ]
            ];
        }
        
        return ['success' => false, 'message' => 'Credenciales incorrectas o usuario inactivo'];
        
    } catch(Exception $e) {
        return ['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()];
    }
}

/**
 * Iniciar sesión del usuario
 */
function iniciarSesion($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['usuario'] = $userData['usuario'];
    $_SESSION['nombre_completo'] = $userData['nombre_completo'];
    $_SESSION['rol'] = $userData['rol'];
    $_SESSION['login_time'] = time();
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
}

/**
 * Verificar si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verificar si el usuario es administrador
 */
function esAdmin() {
    return estaLogueado() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN';
}

/**
 * Requerir autenticación - redirige si no está logueado
 */
function requiereLogin() {
    if (!estaLogueado()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Requerir rol de administrador
 */
function requiereAdmin() {
    requiereLogin();
    if (!esAdmin()) {
        header('Location: index.php?error=acceso_denegado');
        exit();
    }
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Crear nuevo usuario
 */
function crearUsuario($usuario, $password, $nombre_completo, $rol = 'USER') {
    try {
        $db = getDB();
        
        // Verificar si el usuario ya existe
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
        }
        
        // Hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Insertar usuario
        $stmt = $db->prepare("INSERT INTO usuarios (usuario, password, nombre_completo, rol, activo, fecha_creacion) 
                              VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$usuario, $passwordHash, $nombre_completo, $rol]);
        
        return ['success' => true, 'message' => 'Usuario creado exitosamente', 'id' => $db->lastInsertId()];
        
    } catch(Exception $e) {
        return ['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()];
    }
}

/**
 * Obtener datos del usuario actual
 */
function getUsuarioActual() {
    if (!estaLogueado()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'usuario' => $_SESSION['usuario'],
        'nombre_completo' => $_SESSION['nombre_completo'],
        'rol' => $_SESSION['rol']
    ];
}

/**
 * Listar todos los usuarios (solo para admins)
 */
function listarUsuarios() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT id, usuario, nombre_completo, rol, activo, fecha_creacion 
                            FROM usuarios ORDER BY fecha_creacion DESC");
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

/**
 * Cambiar estado de usuario (activar/desactivar)
 */
function cambiarEstadoUsuario($id, $activo) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        $stmt->execute([$activo ? 1 : 0, $id]);
        return ['success' => true];
    } catch(Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>