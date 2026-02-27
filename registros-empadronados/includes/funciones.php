<?php
/**
 * includes/funciones.php
 * Funciones generales del sistema
 */

/**
 * Verifica si el usuario está autenticado
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtiene el rol del usuario actual
 */
function obtenerRolUsuario() {
    return $_SESSION['rol'] ?? null;
}

/**
 * Obtiene el nombre completo del usuario actual
 */
function obtenerNombreUsuario() {
    return $_SESSION['nombre_completo'] ?? 'Usuario';
}

/**
 * Verifica si el usuario está activo
 */
function usuarioActivo() {
    return isset($_SESSION['estado']) && $_SESSION['estado'] === 'Activo';
}

/**
 * Redirige a una página específica
 */
function redirigir($pagina) {
    header("Location: $pagina");
    exit();
}

/**
 * Limpia y sanitiza datos de entrada
 */
function limpiarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

/**
 * Valida el formato de email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Genera un hash seguro para contraseñas
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifica una contraseña contra su hash
 */
function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Formatea números con separadores de miles
 */
function formatearNumero($numero) {
    return number_format($numero, 0, '.', ',');
}

/**
 * Formatea fechas al formato guatemalteco
 */
function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

/**
 * Formatea fecha y hora
 */
function formatearFechaHora($fecha) {
    $timestamp = strtotime($fecha);
    return date('d/m/Y H:i:s', $timestamp);
}

/**
 * Obtiene el departamento del usuario actual
 */
function obtenerDepartamentoUsuario() {
    return $_SESSION['departamento'] ?? null;
}

/**
 * Obtiene el municipio del usuario actual
 */
function obtenerMunicipioUsuario() {
    return $_SESSION['municipio'] ?? null;
}

/**
 * Registra actividad del usuario en logs
 */
function registrarActividad($pdo, $usuario_id, $accion, $descripcion = '') {
    try {
        $sql = "INSERT INTO logs (usuario_id, accion, descripcion, fecha) 
                VALUES (:usuario_id, :accion, :descripcion, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':accion' => $accion,
            ':descripcion' => $descripcion
        ]);
    } catch (PDOException $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

/**
 * Genera un token CSRF
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtiene el avatar o iniciales del usuario
 */
function obtenerIniciales($nombre) {
    $palabras = explode(' ', $nombre);
    if (count($palabras) >= 2) {
        return strtoupper(substr($palabras[0], 0, 1) . substr($palabras[1], 0, 1));
    }
    return strtoupper(substr($nombre, 0, 2));
}

/**
 * Genera un color aleatorio para el avatar
 */
function colorAvatarAleatorio() {
    $colores = [
        '#667eea', '#764ba2', '#f093fb', '#4facfe',
        '#43e97b', '#fa709a', '#fee140', '#30cfd0'
    ];
    return $colores[array_rand($colores)];
}
?>