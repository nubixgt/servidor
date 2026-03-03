<?php
/**
 * API: Autenticación de Login
 * VIDER - MAGA Guatemala
 * Usa hora de Guatemala
 */

session_start();
require_once '../includes/config.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php?error=Método no permitido');
    exit;
}

try {
    $db = Database::getInstance();
    
    // Obtener y sanitizar credenciales
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;

    // Validar campos vacíos
    if (empty($username) || empty($password)) {
        header('Location: ../login.php?error=Por favor complete todos los campos');
        exit;
    }

    // Buscar usuario en la base de datos
    $sql = "SELECT id, username, password, nombre_completo, email, rol, activo 
            FROM usuarios 
            WHERE username = :username 
            LIMIT 1";
    
    $usuario = $db->fetchOne($sql, [':username' => $username]);

    // Verificar si el usuario existe
    if (!$usuario) {
        logError('Intento de login fallido - Usuario no existe: ' . $username);
        header('Location: ../login.php?error=Credenciales incorrectas');
        exit;
    }

    // Verificar si el usuario está activo
    if (!$usuario['activo']) {
        logError('Intento de login - Usuario inactivo: ' . $username);
        header('Location: ../login.php?error=Su cuenta está desactivada. Contacte al administrador.');
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $usuario['password'])) {
        logError('Intento de login fallido - Contraseña incorrecta: ' . $username);
        header('Location: ../login.php?error=Credenciales incorrectas');
        exit;
    }

    // Login exitoso - Crear sesión
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['username'] = $usuario['username'];
    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['login_time'] = time();
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);

    // Actualizar último acceso con hora de Guatemala (desde PHP)
    $horaGuatemala = date('Y-m-d H:i:s');
    $db->query(
        "UPDATE usuarios SET ultimo_acceso = :fecha WHERE id = :id",
        [':fecha' => $horaGuatemala, ':id' => $usuario['id']]
    );

    // Si marcó "Recordarme", extender la duración de la cookie
    if ($remember) {
        $lifetime = 60 * 60 * 24 * 30; // 30 días
        setcookie(session_name(), session_id(), time() + $lifetime, '/');
    }

    // Log de acceso exitoso
    logError('Login exitoso: ' . $username . ' (ID: ' . $usuario['id'] . ') a las ' . $horaGuatemala);

    // Redirigir al dashboard
    header('Location: ../index.php');
    exit;

} catch (Exception $e) {
    logError('Error en autenticación: ' . $e->getMessage());
    header('Location: ../login.php?error=Error del sistema. Intente nuevamente.');
    exit;
}